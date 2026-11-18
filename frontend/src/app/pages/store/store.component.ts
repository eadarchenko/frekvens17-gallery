import { ChangeDetectorRef,Component,OnInit } from '@angular/core';
import { ActivatedRoute,Router } from '@angular/router';
import { ShopService,ShopOrder,ShopUser,Product,Variant } from '../../service/shop.service';
@Component({
  standalone: false,selector:'app-store',templateUrl:'./store.component.html'})
export class StoreComponent implements OnInit {
 trackLine(_index:number,l:{variant_id:number}){return l.variant_id;}
 pendingItems:{variant_id:number;quantity:number}[]=[];
 mode='basket';busy=false;error='';notice='';authMode='login';orders:ShopOrder[]=[];adminOrders:ShopOrder[]=[];products:Product[]=[];
 submitted:ShopOrder|null=null;editor:Product|null=null;tab='works';
 recovery={email:'',token:'',password:'',password_confirmation:''};
 auth={name:'',email:'',password:'',password_confirmation:''};
 customer={name:'',email:'',address:{line1:'',line2:'',city:'',postcode:'',country:''},test_acknowledged:false};
 constructor(public shop:ShopService,private route:ActivatedRoute,private router:Router,private cdr:ChangeDetectorRef){}
 ngOnInit(){this.mode=this.route.snapshot.data['mode'];this.recovery.token=this.route.snapshot.queryParamMap.get('token')||'';this.recovery.email=this.route.snapshot.queryParamMap.get('email')||'';if(this.route.snapshot.queryParamMap.get('verified'))this.notice='Email verified.';if(this.shop.config.hosted_demo)this.customer={name:'DEMO Collector',email:this.shop.user?.email||'guest@demo.test',address:{line1:'DEMO - do not deliver',line2:'',city:'Demo City',postcode:'00000',country:'ES'},test_acknowledged:false};this.customer.name=this.shop.user?.name||this.customer.name;this.customer.email=this.shop.user?.email||this.customer.email;if(this.mode==='account'&&this.shop.user)this.loadOrders();if(this.mode==='admin'&&this.shop.user?.is_admin)this.loadAdmin();}
 async run(fn:()=>Promise<void>){if(this.busy)return;this.busy=true;this.error='';this.notice='';try{await fn();}catch(e){this.error=this.shop.error(e);}finally{this.busy=false;this.cdr.markForCheck();}}
 async refreshBasket(){await this.run(async()=>{await this.shop.refresh();this.notice='Availability refreshed.';});}
 async resend(){await this.run(async()=>{const r=await this.shop.post<{message:string}>('/email/verification-notification',{});this.notice=r.message;});}
 async forgot(){await this.run(async()=>{const r=await this.shop.post<{message:string}>('/forgot-password',{email:this.auth.email});this.notice=r.message;});}
 async reset(){await this.run(async()=>{const r=await this.shop.post<{message:string}>('/reset-password',this.recovery);this.recovery.password='';this.recovery.password_confirmation='';this.notice=r.message;});}
 async authenticate(){await this.run(async()=>{await this.shop.auth(this.authMode,this.auth);this.auth.password='';this.auth.password_confirmation='';await this.loadOrders();});}
 async loadOrders(){try{this.orders=await this.shop.get<ShopOrder[]>('/orders');this.pendingItems=(await this.shop.get<{items:{variant_id:number;quantity:number}[]}>('/pending-checkout')).items;}catch(e){this.error=this.shop.error(e);}finally{this.cdr.markForCheck();}}
 async resumePending(){this.shop.basket=this.pendingItems.map(i=>({...i}));this.shop.saveBasket();sessionStorage.removeItem('f17-checkout-key');await this.router.navigate(['/basket']);}
 async logout(){await this.run(async()=>{await this.shop.logout();this.orders=[];this.pendingItems=[];this.adminOrders=[];this.products=[];this.editor=null;});}
 async profile(){await this.run(async()=>{const r=await this.shop.patch<{user:ShopUser}>('/profile',{name:this.shop.user?.name});this.shop.user=r.user;this.notice='Profile saved.';});}
 async checkout(){await this.run(async()=>{
  let key=sessionStorage.getItem('f17-checkout-key');if(!key){key=crypto.randomUUID();sessionStorage.setItem('f17-checkout-key',key);}
  this.submitted=await this.shop.post<ShopOrder>('/checkout',{...this.customer,address:{...this.customer.address,country:this.customer.address.country.toUpperCase()},items:this.shop.basket,idempotency_key:key});
  this.shop.clear();sessionStorage.removeItem('f17-checkout-key');await this.shop.refresh();
 });}
 async loadAdmin(){await this.run(async()=>{this.products=await this.shop.get<Product[]>('/admin/products');this.adminOrders=await this.shop.get<ShopOrder[]>('/admin/orders');});}
 edit(p?:Product){this.notice='';this.error='';this.editor=p?JSON.parse(JSON.stringify(p)):{id:0,title:'',size:'',description:'',introduction:'',photo:'',photo2:'',active:false,variants:[{name:'Original painting',price_cents:null,stock:0,active:true}]};}
 addVariant(){this.editor?.variants.push({name:'',price_cents:null,stock:0,active:true});}
 price(v:Variant,value:string){v.price_cents=value===''?null:Math.round(Number(value)*100);}
 async save(){if(!this.editor)return;await this.run(async()=>{const p=this.editor!;const saved= p.id ? await this.shop.put<Product>('/admin/products/'+p.id,p):await this.shop.post<Product>('/admin/products',p);await this.shop.refresh();this.products=await this.shop.get<Product[]>('/admin/products');this.editor=saved;this.notice='Work and formats saved.';});}
 async remove(p:Product){if(!confirm('Remove '+p.title+' from the catalogue? Existing order records will be kept.'))return;await this.run(async()=>{await this.shop.delete('/admin/products/'+p.id);this.products=this.products.filter(i=>i.id!==p.id);if(this.editor?.id===p.id)this.editor=null;await this.shop.refresh();this.notice='Work removed. Existing orders kept.';});}
 async upload(event:Event,field:'photo'|'photo2'){const file=(event.target as HTMLInputElement).files?.[0];if(!file||!this.editor)return;await this.run(async()=>{const form=new FormData();form.append('image',file);const result=await this.shop.post<{path:string}>('/admin/images',form);this.editor![field]=result.path;this.notice='Image uploaded. Save the work to apply it.';});}
 async status(o:ShopOrder,value:string){await this.run(async()=>{const updated=await this.shop.patch<ShopOrder>('/admin/orders/'+o.id,{status:value});Object.assign(o,updated);this.notice='Test order updated.';});}
 nextStatuses(o:ShopOrder){return o.status==='received'?['received','preparing','cancelled']:o.status==='preparing'?['preparing','shipped','cancelled']:[o.status];}
}
