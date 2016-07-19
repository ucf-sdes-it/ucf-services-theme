import { Component, ElementRef, OnInit, OnChanges, Output, Input, EventEmitter } from "@angular/core";
import { SafeHtml } from "@angular/platform-browser";
import { Observable } from "rxjs/Rx";
import "rxjs/add/operator/debounceTime";

import { UnescapeHtmlPipe } from "./unescapeHtml.filter";

@Component({
    selector: "ucf-search-form",
    moduleId: module.id,
    templateUrl: "./search-form.component.html",
    // styleUrls: ["../../scss/_search.scss"],
    // directives: [],
    pipes: [ UnescapeHtmlPipe ],
})
export class SearchFormComponent implements OnInit, OnChanges {
    @Input() debounce: number = 350;
    @Input() lead: string = "From orientation to graduation, the UCF experience creates opportunities that last a lifetime. <b>Let's get started</b>";
    @Input() placeholder: string = "What can we help you with today?";
    @Input() action: string = "#";

    frontsearch_query: string = "";
    @Output() search: EventEmitter<string> = new EventEmitter<string>();

    constructor( public elementRef: ElementRef ) {
        window.ucf_comp_searchForm = (window.ucf_comp_searchForm || []).concat(this);
    }

    ngOnInit(): void {
        jQuery("article>section#search-frontpage").hide();
        // Debounce Tutorial: https://manuel-rauber.com/2015/12/31/debouncing-angular-2-input-component/
        const debouncedInputStream = Observable.fromEvent( this.elementRef.nativeElement, 'keyup' )
            .map( () => this.frontsearch_query )
            .debounceTime( this.debounce )
            .distinctUntilChanged();

        debouncedInputStream.subscribe(input => {
            // Don't unload results if user clears search input.
            if( "" !== input ) {
                this.frontsearch_query = input;
                this.search.emit( this.frontsearch_query );
            }
        });
    }

    ngOnChanges(): void {
        this.search.emit( this.frontsearch_query );
    }
}
