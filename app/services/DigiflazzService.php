<?php
namespace App\Services;
use App\Core\Database;
use App\Models\BusinessSetting;
class DigiflazzService {
  private string $username;
  private string $apiKey;
  private string $baseUrl = 'https://api.digiflazz.com/v1';
  public function __construct(){
    $u = BusinessSetting::get('digiflazz_username','');
    $k = BusinessSetting::get('digiflazz_api_key','');
    $envUser = getenv('DIGIFLAZZ_USERNAME')?:'';
    $envKey  = getenv('DIGIFLAZZ_API_KEY')?:'';
    $this->username = $u ?: $envUser;
    $this->apiKey   = $k ?: $envKey;
  }
  public function isConfigured(): bool { return $this->username!=='' && $this->apiKey!==''; }
  public function getUsername(): string { return $this->username; }
  private function sign(string $suffix): string { return md5($this->username . $this->apiKey . $suffix); }
  private function post(string $path, array $body): array {
    $url = $this->baseUrl . $path;
    $json = json_encode($body);
    $ch = curl_init($url);
    curl_setopt_array($ch,[
      CURLOPT_RETURNTRANSFER=>true,
      CURLOPT_POST=>true,
      CURLOPT_POSTFIELDS=>$json,
      CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
      CURLOPT_TIMEOUT=>20,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    $code= curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if($err) return ['success'=>false,'error'=>$err,'http_code'=>$code];
    $data = json_decode($res,true);
    if(!$data) return ['success'=>false,'error'=>'Invalid JSON','raw'=>$res,'http_code'=>$code];
    return ['success'=>true,'data'=>$data,'http_code'=>$code,'raw'=>$res];
  }
  public function cekSaldo(): array {
    if(!$this->isConfigured()) return ['success'=>false,'error'=>'Digiflazz belum dikonfigurasi'];
    return $this->post('/cek-saldo',['cmd'=>'deposit','username'=>$this->username,'sign'=>$this->sign('depo')]);
  }
  public function priceList(string $cmd='prepaid', array $filter=[]): array {
    if(!$this->isConfigured()) return ['success'=>false,'error'=>'Belum dikonfigurasi'];
    $body = ['cmd'=>$cmd,'username'=>$this->username,'sign'=>$this->sign('pricelist')];
    foreach(['code','category','brand','type'] as $k) if(!empty($filter[$k])) $body[$k]=$filter[$k];
    return $this->post('/price-list',$body);
  }
  // prepaid topup
  public function topup(string $buyerSku, string $customerNo, string $refId): array {
    if(!$this->isConfigured()) return ['success'=>false,'error'=>'Belum dikonfigurasi'];
    return $this->post('/transaction',[
      'username'=>$this->username,
      'buyer_sku_code'=>$buyerSku,
      'customer_no'=>$customerNo,
      'ref_id'=>$refId,
      'sign'=>md5($this->username.$this->apiKey.$refId)
    ]);
  }
  // status cek (same endpoint, Digiflazz docs: reuse ref_id)
  public function cekStatus(string $refId): array {
    if(!$this->isConfigured()) return ['success'=>false,'error'=>'Belum dikonfigurasi'];
    // Digiflazz: cek status via /transaction dengan ref_id
    return $this->post('/transaction',[
      'username'=>$this->username,
      'buyer_sku_code'=>'',
      'customer_no'=>'',
      'ref_id'=>$refId,
      'sign'=>md5($this->username.$this->apiKey.$refId)
    ]);
  }
  // pasca inquiry (PLN etc) step 1
  public function inquiryPasca(string $buyerSku, string $customerNo, string $refId): array {
    return $this->topup($buyerSku,$customerNo,$refId);
  }
}
