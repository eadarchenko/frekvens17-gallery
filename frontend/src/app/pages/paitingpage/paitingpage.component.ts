import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { FORMATS, FORMATS_NOTE } from 'src/app/mock-paintings';
import { Painting } from 'src/app/interface/painting';
import { PaintingService } from 'src/app/service/painting.service';

@Component({
  standalone: false,
  selector: 'app-paitingpage',
  templateUrl: './paitingpage.component.html',
  styleUrls: ['./paitingpage.component.scss']
})
export class PaitingpageComponent implements OnInit {
  painting?: Painting;
  prev?: Painting;
  next?: Painting;
  formats = FORMATS;
  formatsNote = FORMATS_NOTE;
  inquiryHref = '';

  constructor(
    private paintingService: PaintingService,
    private route: ActivatedRoute
  ) {}

  ngOnInit() {
    // Re-read when only the :id changes (previous / next links)
    this.route.paramMap.subscribe(params => {
      const id = params.get('id');
      if (!id) { return; }
      this.painting = this.paintingService.getPaintingById(id);
      if (!this.painting) { return; }
      const all = this.paintingService.getPaintings();
      const i = all.findIndex(p => p.id === this.painting!.id);
      this.prev = all[(i - 1 + all.length) % all.length];
      this.next = all[(i + 1) % all.length];
      const subject = encodeURIComponent('Inquiry: ' + this.painting.title);
      this.inquiryHref = 'mailto:art@frekvens17.com?subject=' + subject;
    });
  }
}
