import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { HomeComponent } from './pages/home/home.component';
import { GalleryComponent } from './pages/gallery/gallery.component';
import { AboutComponent } from './pages/about/about.component';
import { ContactComponent } from './pages/contact/contact.component';
import { PaitingpageComponent } from './pages/paitingpage/paitingpage.component';

import { StoreComponent } from './pages/store/store.component';

const routes: Routes = [
  {path:'reset-password',component:StoreComponent,data:{mode:'reset'}},
  {path:'basket',component:StoreComponent,data:{mode:'basket'}},
  {path:'account',component:StoreComponent,data:{mode:'account'}},
  {path:'admin',component:StoreComponent,data:{mode:'admin'}},
  { path: '', component: HomeComponent },
  { path: 'gallery', component: GalleryComponent },
  { path: 'about', component: AboutComponent },
  { path: 'contact', component: ContactComponent },
  { path: 'paiting/:id', component: PaitingpageComponent },
  // The Collections page was an empty placeholder: send old links to the gallery.
  { path: 'collections', redirectTo: 'gallery', pathMatch: 'full' },
  { path: '**', redirectTo: '' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes, {
    bindToComponentInputs: true,
    scrollPositionRestoration: 'top'
  })],
  exports: [RouterModule]
})
export class AppRoutingModule { }
