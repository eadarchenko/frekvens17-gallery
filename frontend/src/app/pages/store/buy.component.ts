import { Component,Input } from '@angular/core';
import { ShopService,Product } from '../../service/shop.service';
@Component({
  standalone: false,selector:'app-buy',template:`
<section class="purchase-panel" *ngIf="product">
 <h3>Choose a format</h3>
 <div class="format-option" *ngFor="let v of product.variants">
  <div><strong>{{v.name}}</strong><span>{{v.price_cents===null ? 'Price on request' : shop.money(v.price_cents)}}</span>
  <small>{{v.price_cents===null ? 'Not available for online purchase yet.' : v.stock>0 ? v.stock+' available' : 'Out of stock'}}</small></div>
  <button class="btn" type="button" [disabled]="v.price_cents===null || v.stock<1" (click)="shop.add(v);added=true">Add to basket</button>
 </div>
 <p class="success" role="status" *ngIf="added">Added to your basket. <a routerLink="/basket">View basket →</a></p>
 <small>Online payment is not connected. Test orders only when enabled.</small>
</section>`})
export class BuyComponent { @Input() id=0;added=false;constructor(public shop:ShopService){}get product():Product|undefined{return this.shop.products.find(p=>p.id===this.id);} }
