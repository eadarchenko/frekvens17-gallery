import { Component } from '@angular/core';
import { Painting } from 'src/app/interface/painting';
import { paintings} from 'src/app/mock-paintings';
import { PaintingService } from 'src/app/service/painting.service';

@Component({
  standalone: false,
  selector: 'app-gallery',
  templateUrl: './gallery.component.html',
  styleUrls: ['./gallery.component.scss']
})
export class GalleryComponent {

  paitings: Painting[] = [];

  constructor(private paintingService: PaintingService) { }

  ngOnInit(): void {
    this.paitings = this.paintingService.getPaintings();
  }
}
