import { HttpClientModule } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { ShopService } from './service/shop.service';
import { StoreComponent } from './pages/store/store.component';
import { BuyComponent } from './pages/store/buy.component';
import { APP_INITIALIZER, NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { NavbarComponent } from './components/navbar/navbar.component';
import { FooterComponent } from './components/footer/footer.component';
import { HomeComponent } from './pages/home/home.component';
import { GalleryComponent } from './pages/gallery/gallery.component';
import { CollectionsComponent } from './pages/collections/collections.component';
import { AboutComponent } from './pages/about/about.component';
import { ContactComponent } from './pages/contact/contact.component';
import { InformationComponent } from './information/information.component';
import { PaitingpageComponent } from './pages/paitingpage/paitingpage.component';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { MatExpansionModule } from '@angular/material/expansion';


@NgModule({
  declarations: [
    AppComponent, StoreComponent, BuyComponent,
    NavbarComponent,
    FooterComponent,
    HomeComponent,
    GalleryComponent,
    CollectionsComponent,
    AboutComponent,
    ContactComponent,
    InformationComponent,
    PaitingpageComponent,
  ],
  imports: [
    BrowserModule, HttpClientModule, FormsModule,
    AppRoutingModule,
    BrowserAnimationsModule,
     MatExpansionModule
  ],
  providers: [{provide:APP_INITIALIZER,useFactory:(s:ShopService)=>()=>s.init(),deps:[ShopService],multi:true}],
  bootstrap: [AppComponent]
})
export class AppModule { }
