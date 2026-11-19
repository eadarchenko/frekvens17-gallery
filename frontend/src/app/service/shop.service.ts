import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';
import { Painting } from '../interface/painting';
export interface Variant { id?:number; product_id?:number; name:string; price_cents:number|null; stock:number; active:boolean; }
export interface Product extends Painting { active:boolean; variants:Variant[]; }
export interface ShopUser { id:number;name:string;email:string;is_admin:boolean;email_verified_at:string|null; }
export interface ShopConfig { paypal_mode?:'preview'|'sandbox'|'disabled';mode:string;currency:string|null;shipping_cents:number;tax_basis_points:number;payments_connected:boolean;demo?:boolean;hosted_demo?:boolean; }
export interface OrderLine { title:string;variant:string;price_cents:number;quantity:number; }
export interface ShopOrder { id:number;reference:string;name:string;email:string;address:{line1:string;line2?:string;city:string;postcode:string;country:string};currency:string;subtotal_cents:number;shipping_cents:number;tax_cents:number;total_cents:number;status:string;payment_status:string;is_test:boolean;created_at:string;items:OrderLine[]; }
export interface BasketLine { variant_id:number; quantity:number; }
@Injectable({providedIn:'root'})
export class ShopService {
 products:Product[]=[];user:ShopUser|null=null; unavailable='';
 config:ShopConfig={mode:'disabled',currency:null,shipping_cents:0,tax_basis_points:0,payments_connected:false};
 basket:BasketLine[]=[];
 constructor(private http:HttpClient) {
  try {const data=JSON.parse(localStorage.getItem('f17-basket')||'[]');if(Array.isArray(data))this.basket=data.filter(l=>Number.isInteger(l.variant_id)&&Number.isInteger(l.quantity)&&l.quantity>0&&l.quantity<=25).slice(0,40);}catch{}
 }
 async init() {try {const session=await this.get<{user:ShopUser|null}>('/session');this.user=session.user;await this.refresh();this.config=await this.get<ShopConfig>('/config');}catch {this.unavailable='The shop server is unavailable. Please try again later.';} }
 async refresh(){this.products=await this.get<Product[]>('/products');}
 get<T>(url:string){return firstValueFrom(this.http.get<T>('/api'+url));}
 post<T>(url:string,body:unknown){return firstValueFrom(this.http.post<T>('/api'+url,body));}
 put<T>(url:string,body:unknown){return firstValueFrom(this.http.put<T>('/api'+url,body));}
 patch<T>(url:string,body:unknown){return firstValueFrom(this.http.patch<T>('/api'+url,body));}
 delete<T>(url:string){return firstValueFrom(this.http.delete<T>('/api'+url));}
 money(cents:number,currency?:string|null){return currency||this.config.currency ? new Intl.NumberFormat('en-GB',{style:'currency',currency:currency||this.config.currency!}).format(cents/100):(cents/100).toFixed(2)+' (currency not set)';}
 get count(){return this.basket.reduce((s,l)=>s+l.quantity,0);}
 variant(id:number){for(const p of this.products){const v=p.variants.find(v=>v.id===id);if(v)return {product:p,variant:v};}return null;}
 get lines(){return this.basket.map(l=>({...l,...{entry:this.variant(l.variant_id)}}));}
 get subtotal(){return this.lines.reduce((s,l)=>s+(l.entry?.variant.price_cents??0)*l.quantity,0);}
 get tax(){return Math.round(this.subtotal*this.config.tax_basis_points/10000);}
 get total(){return this.subtotal+this.tax+this.config.shipping_cents;}
 get valid(){return this.basket.length>0&&this.lines.every(l=>l.entry&&l.entry.variant.active&&l.entry.variant.price_cents!==null&&l.quantity<=l.entry.variant.stock);}
 saveBasket(){try{localStorage.setItem('f17-basket',JSON.stringify(this.basket));}catch{} }
 add(v:Variant){if(!v.id||v.price_cents===null||v.stock<1)return;const l=this.basket.find(l=>l.variant_id===v.id);if(l){l.quantity=Math.min(l.quantity+1,v.stock,25);}else this.basket.push({variant_id:v.id,quantity:1});this.saveBasket();}
 quantity(id:number,n:number){const l=this.basket.find(l=>l.variant_id===id);if(l)l.quantity=Math.max(1,Math.min(25,Math.floor(n)||1));this.saveBasket();}
 remove(id:number){this.basket=this.basket.filter(l=>l.variant_id!==id);this.saveBasket();}
 clear(){this.basket=[];this.saveBasket();}
 error(e:any){const errors=e?.error?.errors;if(errors)return Object.values(errors).flat().join(' ');if(e?.status===419)return 'Session expired. Reload this page and try again.';return e?.error?.message||'Unable to reach the server. Please try again.';}
 async auth(kind:string,body:unknown){const r=await this.post<{user:ShopUser}>('/'+kind,body);this.user=r.user;}
 async demoRole(role:string){await this.post('/demo/role',{role});this.clear();sessionStorage.removeItem('f17-checkout-key');location.href=role==='artist'?'/admin':role==='collector'?'/account':'/gallery';}
 async logout(){await this.post('/logout',{});this.user=null;await this.get('/session');}
}
