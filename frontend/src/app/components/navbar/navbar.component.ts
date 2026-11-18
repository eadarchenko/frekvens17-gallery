import { ShopService } from '../../service/shop.service';
import { Component } from '@angular/core';

@Component({
  standalone: false,
  selector: 'app-navbar',
  templateUrl: './navbar.component.html',
  styleUrls: ['./navbar.component.scss']
})
export class NavbarComponent {
 constructor(public shop:ShopService){}

}
