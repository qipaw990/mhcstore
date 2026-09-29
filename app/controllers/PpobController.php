<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Database;
use App\Models\BusinessSetting;
use App\Models\Wallet;
use App\Services\DigiflazzService;

class PpobController extends Controller {

  // GET /api/v1/ppob/catalog?category=Pulsa&brand=TELKOMSEL
  public function catalog(): void {
    $cat = $_GET['category'] ?? '';
    $brand = $_GET['brand'] ?? '';
    $q = "SELECT * FROM ppob_products WHERE is_active=1";
    $p=[];
    if($cat!==''){ $q.=" AND category=?"; $p[]=$cat; }
    if($brand!==''){ $q.=" AND brand=?"; $p[]=$brand; }
    $q.=" ORDER BY price ASC LIMIT 300";
    $rows = Database::query($q,$p);
    $this->json(['success'=>true,'data'=>$rows]);
  }

  public function categories(): void {
    $rows = Database::query("SELECT category, COUNT(*) c FROM ppob_products WHERE is_active=1 GROUP BY category");
    $this->json(['success'=>true,'data'=>$rows]);
  }

  // POST /api/v1/ppob/purchase {buyer_sku_code, customer_no}
  public function purchase(): void {
    $user = auth_user(); if(!$user || empty($user['id'])){ $this->json(['success'=>false,'message'=>'Unauthorized'],401); return; }
    $body = $this->getJsonBody();
    $sku = trim($body['buyer_sku_code'] ?? $body['sku'] ?? '');
    $cust= trim($body['customer_no'] ?? $body['phone'] ?? '');
    if($sku==='' || $cust===''){ $this->json(['success'=>false,'message'=>'SKU & nomor tujuan wajib'],422); return; }
    $prod = Database::fetchOne("SELECT * FROM ppob_products WHERE buyer_sku_code=? LIMIT 1",[$sku]);
    if(!$prod){ $this->json(['success'=>false,'message'=>'Produk tidak ditemukan'],404); return; }
    if(!(int)$prod['is_active']){ $this->json(['success'=>false,'message'=>'Produk nonaktif'],422); return; }

    $sellPrice = (int)$prod['selling_price'];
    $wallet = (new Wallet())->getOrCreate((int)$user['id'],'customer');
    if((float)$wallet['balance'] < $sellPrice){
      $this->json(['success'=>false,'message'=>'Saldo CicalengkaPay tidak cukup','balance'=>(float)$wallet['balance'],'need'=>$sellPrice],402); return;
    }
    $refId = 'PPOB-'.$user['id'].'-'.time().'-'.rand(100,999);
    try{
      (new Wallet())->debit((int)$user['id'],$sellPrice,'ppob_purchase',"PPOB {$prod['product_name']} ke $cust", $refId, 'customer');
    }catch(\Throwable $e){
      $this->json(['success'=>false,'message'=>$e->getMessage()],402); return;
    }

    $tid = Database::insert('ppob_transactions',[
      'user_id'=>(int)$user['id'],
      'ref_id'=>$refId,
      'buyer_sku_code'=>$sku,
      'customer_no'=>$cust,
      'product_name'=>$prod['product_name'],
      'price'=>$prod['price'],
      'selling_price'=>$sellPrice,
      'status'=>'pending',
      'message'=>null,
      'raw_response'=>null
    ]);

    $svc = new DigiflazzService();
    $res = $svc->topup($sku,$cust,$refId);
    $status='pending'; $msg='Diproses'; $trxId=null;
    if(isset($res['data'])){
      $d = $res['data']['data'] ?? $res['data'];
      $statusRaw = strtolower($d['status'] ?? '');
      $msg = $d['message'] ?? $d['rc'] ?? json_encode($d);
      $trxId = $d['sn'] ?? $d['trx_id'] ?? null;
      if(str_contains($statusRaw,'sukses') || $statusRaw==='success') $status='sukses';
      elseif(str_contains($statusRaw,'gagal') || str_contains($statusRaw,'failed')) $status='gagal';
      else $status='pending';
    } else {
      $msg = $res['error'] ?? 'Gagal hubungi Digiflazz';
    }

    Database::execute("UPDATE ppob_transactions SET status=?, digiflazz_trx_id=?, raw_response=?, message=? WHERE id=?",
      [$status, $trxId, json_encode($res, JSON_UNESCAPED_UNICODE), $msg, $tid]);

    if($status==='gagal'){
      try{ (new Wallet())->credit((int)$user['id'],$sellPrice,'refund',"Refund PPOB gagal $refId",$refId,'customer'); }catch(\Throwable $e){}
      $this->json(['success'=>false,'message'=>'Transaksi gagal: '.$msg,'ref_id'=>$refId,'status'=>'gagal'],502); return;
    }

    $this->json(['success'=>true,'message'=>$msg,'ref_id'=>$refId,'status'=>$status,'selling_price'=>$sellPrice]);
  }

  public function history(): void {
    $user = auth_user(); if(!$user || empty($user['id'])){ $this->json(['success'=>false,'message'=>'Unauthorized'],401); return; }
    $rows = Database::query("SELECT * FROM ppob_transactions WHERE user_id=? ORDER BY id DESC LIMIT 50",[(int)$user['id']]);
    $this->json(['success'=>true,'data'=>$rows]);
  }

  public function status(array $params=[]): void {
    $ref = $params['ref_id'] ?? $_GET['ref_id'] ?? '';
    if($ref===''){ $this->json(['success'=>false,'message'=>'ref_id wajib'],422); return; }
    $trx = Database::fetchOne("SELECT * FROM ppob_transactions WHERE ref_id=? LIMIT 1",[$ref]);
    if(!$trx){ $this->json(['success'=>false,'message'=>'Transaksi tidak ditemukan'],404); return; }
    if($trx['status']==='pending'){
      $svc = new DigiflazzService();
      $res = $svc->cekStatus($ref);
      if(isset($res['data'])){
        $d = $res['data']['data'] ?? $res['data'];
        $st = strtolower($d['status'] ?? '');
        $ns='pending';
        if(str_contains($st,'sukses')||$st==='success') $ns='sukses';
        elseif(str_contains($st,'gagal')||str_contains($st,'failed')) $ns='gagal';
        if($ns!==$trx['status']){
          Database::execute("UPDATE ppob_transactions SET status=?, raw_response=?, message=? WHERE ref_id=?",
            [$ns, json_encode($res, JSON_UNESCAPED_UNICODE), $d['message']??$d['rc']??'', $ref]);
          if($ns==='gagal'){
            try{ (new Wallet())->credit((int)$trx['user_id'], (float)$trx['selling_price'],'refund',"Refund PPOB gagal $ref",$ref,'customer'); }catch(\Throwable $e){}
          }
          $trx['status']=$ns;
        }
      }
    }
    $this->json(['success'=>true,'data'=>$trx]);
  }

  // Admin sync catalog
  public function adminSyncPrice(): void {
    $svc = new DigiflazzService();
    if(!$svc->isConfigured()){ $this->json(['success'=>false,'message'=>'Digiflazz belum dikonfigurasi'],500); return; }
    $r = $svc->priceList('prepaid');
    if(empty($r['success']) || empty($r['data']['data'])){
      $this->json(['success'=>false,'message'=>'Gagal ambil price list','raw'=>$r],502); return;
    }
    $list = $r['data']['data'];
    $markup = (int)(BusinessSetting::get('digiflazz_markup_default','1500'));
    $n=0;
    foreach($list as $it){
      if(empty($it['buyer_sku_code'])) continue;
      if(empty($it['buyer_product_status']) || empty($it['seller_product_status'])) continue;
      $sku=$it['buyer_sku_code'];
      $price=(int)($it['price'] ?? 0);
      $sell=$price + $markup;
      $exists = Database::fetchOne("SELECT id FROM ppob_products WHERE buyer_sku_code=? LIMIT 1",[$sku]);
      $row=[
        'buyer_sku_code'=>$sku,
        'product_name'=>$it['product_name'] ?? $sku,
        'category'=>$it['category'] ?? 'Lainnya',
        'brand'=>$it['brand'] ?? '',
        'type'=>$it['type'] ?? '',
        'seller_name'=>$it['seller_name'] ?? '',
        'price'=>$price,
        'selling_price'=>$sell,
        'is_active'=>1,
        'stock'=>$it['unlimited_stock']? -1 : (int)($it['stock']??0),
        'desc_text'=>$it['desc'] ?? ''
      ];
      if($exists){
        Database::update('ppob_products',$row,'buyer_sku_code=?',[$sku]);
      } else {
        try{ Database::insert('ppob_products',$row); }catch(\Throwable $e){}
      }
      $n++;
    }
    $this->json(['success'=>true,'synced'=>$n,'total'=>count($list)]);
  }

  public function adminCekSaldo(): void {
    $svc=new DigiflazzService();
    $r=$svc->cekSaldo();
    $this->json($r);
  }

  // Admin web view
  public function adminProducts(): void {
    $rows = Database::query("SELECT * FROM ppob_products ORDER BY category, price LIMIT 300");
    $markup = (int)(BusinessSetting::get('digiflazz_markup_default','1500'));
    $this->view('admin/ppob_products', ['products'=>$rows,'markup'=>$markup]);
  }
  public function adminTransactions(): void {
    $rows = Database::query("SELECT t.*, u.name, u.phone FROM ppob_transactions t LEFT JOIN users u ON u.id=t.user_id ORDER BY t.id DESC LIMIT 100");
    $this->view('admin/ppob_transactions', ['rows'=>$rows]);
  }
  public function adminToggle(): void {
    $sku = $this->getPost('sku','');
    if($sku===''){ $this->json(['success'=>false,'message'=>'sku wajib'],422); return; }
    $p = Database::fetchOne("SELECT * FROM ppob_products WHERE buyer_sku_code=? LIMIT 1",[$sku]);
    if(!$p){ $this->json(['success'=>false,'message'=>'Tidak ditemukan'],404); return; }
    $nv = ((int)$p['is_active'])?0:1;
    Database::execute("UPDATE ppob_products SET is_active=? WHERE buyer_sku_code=?",[$nv,$sku]);
    $this->json(['success'=>true,'is_active'=>$nv]);
  }
  public function adminSaveMarkup(): void {
    $m = (int)$this->getPost('markup',1500);
    BusinessSetting::set('digiflazz_markup_default',(string)$m);
    Database::execute("UPDATE ppob_products SET selling_price = price + ?",[$m]);
    $this->json(['success'=>true,'markup'=>$m]);
  }
}
