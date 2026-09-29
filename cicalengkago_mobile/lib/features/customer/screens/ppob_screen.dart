import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../core/widgets/app_alert.dart';
import '../controllers/customer_controller.dart';
import '../services/ppob_service.dart';

class PpobScreen extends StatefulWidget {
  const PpobScreen({super.key});
  @override State<PpobScreen> createState() => _PpobScreenState();
}

class _PpobScreenState extends State<PpobScreen> with SingleTickerProviderStateMixin {
  late TabController _tab;
  final _phoneCtrl = TextEditingController();
  String _cat = 'Pulsa';
  List<dynamic> _products = [];
  List<dynamic> _history = [];
  bool _loading = true;
  bool _buying = false;
  String? _selectedSku;
  Map<String,dynamic>? _selectedProd;

  final _cats = const [
    {'key':'Pulsa','label':'Pulsa','icon':Icons.phone_android_rounded},
    {'key':'Data','label':'Paket Data','icon':Icons.wifi_rounded},
    {'key':'PLN','label':'PLN','icon':Icons.bolt_rounded},
    {'key':'E-Money','label':'E-Money','icon':Icons.account_balance_wallet_rounded},
  ];

  @override void initState(){ super.initState(); _tab=TabController(length:2, vsync:this); _load(); }
  @override void dispose(){ _tab.dispose(); _phoneCtrl.dispose(); super.dispose(); }

  Future<void> _load() async {
    setState(()=>_loading=true);
    final res = await PpobService.catalog(category: _cat);
    final h = await PpobService.history();
    if(!mounted) return;
    setState((){
      _products = (res['success']==true ? (res['data'] as List? ?? []) : []);
      _history = (h['success']==true ? (h['data'] as List? ?? []) : []);
      _loading=false;
      _selectedSku=null; _selectedProd=null;
    });
  }

  Future<void> _buy() async {
    final phone=_phoneCtrl.text.trim();
    if(phone.length<9){ AppAlert.show(context,message:'Nomor tujuan tidak valid',isSuccess:false); return; }
    if(_selectedSku==null){ AppAlert.show(context,message:'Pilih nominal dulu',isSuccess:false); return; }
    setState(()=>_buying=true);
    final res = await PpobService.purchase(sku:_selectedSku!, customerNo:phone);
    if(!mounted) return;
    setState(()=>_buying=false);
    if(res['success']==true){
      AppAlert.show(context,message: res['message']?.toString() ?? 'Berhasil — diproses',isSuccess:true);
      try{ context.read<CustomerController>().fetchWallet(); }catch(_){}
      _load();
      _tab.animateTo(1);
    } else {
      final msg=res['message']?.toString() ?? 'Gagal';
      final need=res['need'];
      AppAlert.show(context,message: need!=null ? '$msg (butuh ${CurrencyFormatter.formatRupiah(double.tryParse(need.toString())??0)})' : msg,isSuccess:false);
    }
  }

  @override Widget build(BuildContext context){
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white, elevation:0,
        leading: IconButton(icon: const Icon(Icons.arrow_back_rounded,color:Color(0xFF0F172A)), onPressed: ()=>Navigator.pop(context)),
        title: const Text('Pulsa & PPOB', style:TextStyle(color:Color(0xFF0F172A),fontWeight:FontWeight.w800,fontSize:16)),
        centerTitle:true,
        bottom: TabBar(controller:_tab, labelColor:AppTheme.primaryRed, unselectedLabelColor:const Color(0xFF64748B), indicatorColor:AppTheme.primaryRed, tabs: const [Tab(text:'Beli'), Tab(text:'Riwayat')]),
      ),
      body: TabBarView(controller:_tab, children:[_buildBuy(), _buildHistory()]),
    );
  }

  Widget _buildBuy(){
    if(_loading) return const Center(child:CircularProgressIndicator());
    return ListView(padding: const EdgeInsets.fromLTRB(16,14,16,24), children:[
      Container(padding: const EdgeInsets.all(14), decoration: BoxDecoration(color:Colors.white,borderRadius:BorderRadius.circular(16), border:Border.all(color:const Color(0xFFE2E8F0))), child: Column(crossAxisAlignment:CrossAxisAlignment.start, children:[
        const Text('Nomor tujuan', style:TextStyle(fontWeight:FontWeight.w700,fontSize:12,color:Color(0xFF0F172A))),
        const SizedBox(height:8),
        TextField(controller:_phoneCtrl, keyboardType:TextInputType.phone, inputFormatters:[FilteringTextInputFormatter.digitsOnly], decoration: InputDecoration(hintText:'08xxxxxxxxxx / ID PLN 11 digit', hintStyle:const TextStyle(color:Color(0xFF94A3B8),fontSize:13), filled:true, fillColor:const Color(0xFFF8FAFC), contentPadding:const EdgeInsets.symmetric(horizontal:14,vertical:13), border:OutlineInputBorder(borderRadius:BorderRadius.circular(12), borderSide:const BorderSide(color:Color(0xFFE2E8F0))), enabledBorder:OutlineInputBorder(borderRadius:BorderRadius.circular(12), borderSide:const BorderSide(color:Color(0xFFE2E8F0))), focusedBorder:OutlineInputBorder(borderRadius:BorderRadius.circular(12), borderSide:const BorderSide(color:AppTheme.primaryRed,width:1.2))), style:const TextStyle(fontSize:14,fontWeight:FontWeight.w600)),
        const SizedBox(height:6),
        const Text('Bayar pakai saldo CicalengkaPay. Pastikan saldo cukup.', style:TextStyle(fontSize:11,color:Color(0xFF64748B))),
      ])),
      const SizedBox(height:14),
      SizedBox(height:42, child: ListView.separated(scrollDirection:Axis.horizontal, itemCount:_cats.length, separatorBuilder:(_,__)=>const SizedBox(width:8), itemBuilder:(_,i){
        final c=_cats[i]; final sel=_cat==c['key'];
        return ChoiceChip(
          label: Row(mainAxisSize:MainAxisSize.min, children:[Icon(c['icon'] as IconData,size:16,color: sel?Colors.white:const Color(0xFF475569)), const SizedBox(width:6), Text(c['label'] as String)]),
          selected:sel, onSelected:(_){ setState(()=>_cat=c['key'] as String); _load(); },
          selectedColor:AppTheme.primaryRed, backgroundColor:Colors.white,
          labelStyle: TextStyle(color: sel?Colors.white:const Color(0xFF334155), fontWeight:FontWeight.w700, fontSize:12),
          side: BorderSide(color: sel?AppTheme.primaryRed:const Color(0xFFE2E8F0)),
          shape: RoundedRectangleBorder(borderRadius:BorderRadius.circular(22)),
        );
      })),
      const SizedBox(height:14),
      if(_products.isEmpty) Container(padding: const EdgeInsets.all(24), decoration: BoxDecoration(color:Colors.white,borderRadius:BorderRadius.circular(16), border:Border.all(color:const Color(0xFFE2E8F0))), child: const Column(children:[Icon(Icons.sim_card_rounded,size:28,color:Color(0xFFCBD5E1)), SizedBox(height:8), Text('Belum ada produk kategori ini', style:TextStyle(color:Color(0xFF64748B),fontSize:12))])),
      if(_products.isNotEmpty) GridView.builder(shrinkWrap:true, physics: const NeverScrollableScrollPhysics(), gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount:2, mainAxisSpacing:10, crossAxisSpacing:10, childAspectRatio:1.55), itemCount:_products.length, itemBuilder:(_,i){
        final p=_products[i] as Map;
        final sel=_selectedSku==p['buyer_sku_code'];
        final price=double.tryParse(p['selling_price']?.toString() ?? '0') ?? 0;
        return InkWell(onTap: ()=>setState((){ _selectedSku=p['buyer_sku_code']?.toString(); _selectedProd=p as Map<String,dynamic>; }), borderRadius:BorderRadius.circular(14), child: Container(padding: const EdgeInsets.all(12), decoration: BoxDecoration(color: sel? const Color(0xFFFFF1F2):Colors.white, borderRadius:BorderRadius.circular(14), border:Border.all(color: sel?AppTheme.primaryRed:const Color(0xFFE2E8F0), width: sel?1.4:1)), child: Column(crossAxisAlignment:CrossAxisAlignment.start, children:[
          Text(p['product_name']?.toString() ?? '-', maxLines:2, overflow:TextOverflow.ellipsis, style: TextStyle(fontWeight:FontWeight.w800,fontSize:12,color: sel?const Color(0xFF881337):const Color(0xFF0F172A))),
          const Spacer(),
          Text(CurrencyFormatter.formatRupiah(price), style: TextStyle(fontWeight:FontWeight.w800,fontSize:13,color: sel?AppTheme.primaryRed:const Color(0xFF0F172A))),
          if(p['brand']!=null && p['brand'].toString().isNotEmpty) Text(p['brand'].toString(), style: const TextStyle(fontSize:10,color:Color(0xFF64748B))),
        ])));
      }),
      const SizedBox(height:18),
      SizedBox(width:double.infinity, height:48, child: ElevatedButton(onPressed: _buying?null:_buy, style: ElevatedButton.styleFrom(backgroundColor:AppTheme.primaryRed, foregroundColor:Colors.white, shape:RoundedRectangleBorder(borderRadius:BorderRadius.circular(14)), elevation:0), child: _buying? const SizedBox(width:18,height:18, child:CircularProgressIndicator(strokeWidth:2,color:Colors.white)) : Text(_selectedProd==null ? 'Pilih nominal' : 'Bayar ${CurrencyFormatter.formatRupiah(double.tryParse(_selectedProd!['selling_price']?.toString() ?? '0') ?? 0)}', style: const TextStyle(fontWeight:FontWeight.w800,fontSize:13)))),
      const SizedBox(height:8),
      const Text('Harga sudah termasuk markup toko. Status pending akan otomatis dicek di Riwayat.', textAlign:TextAlign.center, style:TextStyle(fontSize:11,color:Color(0xFF94A3B8))),
    ]);
  }

  Widget _buildHistory(){
    if(_loading) return const Center(child:CircularProgressIndicator());
    if(_history.isEmpty) return const Center(child: Padding(padding:EdgeInsets.all(24), child:Text('Belum ada transaksi PPOB', style:TextStyle(color:Color(0xFF64748B)))));
    return RefreshIndicator(onRefresh:_load, child: ListView.separated(padding: const EdgeInsets.all(16), itemCount:_history.length, separatorBuilder:(_,__)=>const SizedBox(height:10), itemBuilder:(_,i){
      final t=_history[i] as Map;
      final st=(t['status']?.toString() ?? 'pending').toLowerCase();
      Color c; String label;
      if(st=='sukses'){ c=const Color(0xFF16A34A); label='Sukses'; } else if(st=='gagal'){ c=const Color(0xFFDC2626); label='Gagal'; } else { c=const Color(0xFFD97706); label='Pending'; }
      return Container(padding: const EdgeInsets.all(13), decoration: BoxDecoration(color:Colors.white,borderRadius:BorderRadius.circular(14), border:Border.all(color:const Color(0xFFE2E8F0))), child: Row(children:[
        Container(width:38,height:38,decoration: BoxDecoration(color: c.withValues(alpha:0.12), borderRadius:BorderRadius.circular(10)), child: Icon(st=='sukses'?Icons.check_circle_rounded: st=='gagal'?Icons.error_rounded: Icons.hourglass_top_rounded, color:c, size:18)),
        const SizedBox(width:10),
        Expanded(child: Column(crossAxisAlignment:CrossAxisAlignment.start, children:[
          Text(t['product_name']?.toString() ?? '-', style: const TextStyle(fontWeight:FontWeight.w700,fontSize:12,color:Color(0xFF0F172A)), maxLines:1, overflow:TextOverflow.ellipsis),
          const SizedBox(height:2),
          Text('${t['customer_no'] ?? '-'} • ${CurrencyFormatter.formatRupiah(double.tryParse(t['selling_price']?.toString() ?? '0') ?? 0)}', style: const TextStyle(fontSize:11,color:Color(0xFF64748B))),
          const SizedBox(height:2),
          Text(t['ref_id']?.toString() ?? '', style: const TextStyle(fontSize:10,color:Color(0xFF94A3B8))),
        ])),
        const SizedBox(width:8),
        Container(padding: const EdgeInsets.symmetric(horizontal:8,vertical:4), decoration: BoxDecoration(color:c.withValues(alpha:0.12), borderRadius:BorderRadius.circular(20)), child: Text(label, style: TextStyle(fontSize:10,fontWeight:FontWeight.w800,color:c))),
      ]));
    }));
  }
}
