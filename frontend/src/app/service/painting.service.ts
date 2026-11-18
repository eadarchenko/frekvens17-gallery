import { Injectable } from '@angular/core';
import { Painting } from '../interface/painting';
import { ShopService } from './shop.service';
@Injectable({providedIn:'root'})
export class PaintingService {
 constructor(private shop:ShopService){}
 getPaintings():Painting[]{return this.shop.products;}
 getPaintingById(id:string|number):Painting|undefined{return this.shop.products.find(p=>p.id===Number(id));}
}
