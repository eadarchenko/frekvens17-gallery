import { platformBrowser } from '@angular/platform-browser';
import { provideZoneChangeDetection } from '@angular/core';
import { AppModule } from './app/app.module';

platformBrowser().bootstrapModule(AppModule, {applicationProviders:[provideZoneChangeDetection({eventCoalescing:true})]}).catch(err=>console.error(err));
