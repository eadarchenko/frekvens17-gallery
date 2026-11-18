import { Component } from '@angular/core';
import { Painting } from 'src/app/interface/painting';
import { PaintingService } from 'src/app/service/painting.service';

@Component({
  standalone: false,
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.scss']
})
export class HomeComponent {
  featured: Painting[] = [];

  constructor(private paintingService: PaintingService) {
    // Four works shown on the front page: Point of Transition, Unfolding Presence, Memory of Water, The Phenomenon
    this.featured = [1, 2, 4, 8]
      .map(id => this.paintingService.getPaintingById(id))
      .filter((p): p is Painting => !!p);
  }
}
