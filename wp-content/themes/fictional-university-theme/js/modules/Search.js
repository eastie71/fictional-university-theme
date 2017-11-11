import $ from 'jquery';

class Search {
	// 1) Describe and initiate the object
	constructor() {
		this.openButton = $(".js-search-trigger");
		this.closeButton = $(".search-overlay__close");
		this.searchOverlay = $(".search-overlay");
		this.searchField = $("#search-term");
		this.searchResults = $("#search-overlay__results");
		this.events();
		this.isSearchOverlayOpen = false;
		this.isSpinnerVisible = false;
		this.previousSearchText;
		this.typingTimer;
	}

	// 2) Events
	events() {
		this.openButton.on("click", this.openOverlay.bind(this));
		this.closeButton.on("click", this.closeOverlay.bind(this));
		$(document).on("keydown", this.keyPressHandler.bind(this));
		this.searchField.on("keyup", this.searchText.bind(this))
	}
	
	// 3) Methods (function, action...)
	openOverlay() {
		this.searchOverlay.addClass("search-overlay--active");
		// remove the scrolling of the page when search overlay modal opens
		$("body").addClass("body-no-scroll");
		this.isSearchOverlayOpen = true;
	}

	closeOverlay() {
		this.searchOverlay.removeClass("search-overlay--active");
		// re-enable the scrolling of the page when search overlay modal is closed
		$("body").removeClass("body-no-scroll");
		this.isSearchOverlayOpen = false;
	}

	keyPressHandler(e) {
		// Open the Search window if 's' key is pressed and NOT inside a text input field
		if (e.keyCode == 83 && !this.isSearchOverlayOpen && $("input, textarea").is(':focus')) {
			this.openOverlay();
		// Close the search window if ESC key is pressed
		} else if (e.keyCode == 27 && this.isSearchOverlayOpen) {
			this.closeOverlay();
		}
	}

	searchText() {
		if (this.searchField.val() != this.previousSearchText) {
			clearTimeout(this.typingTimer);
			if (this.searchField.val()) {
				if (!this.isSpinnerVisible) {
					this.searchResults.html('<div class="spinner-loader"></div>')
					this.isSpinnerVisible = true;
				}
				this.typingTimer = setTimeout(this.getSearchResults.bind(this), 2000);
			} else {
				this.searchResults.html('');
				this.isSpinnerVisible = false;
			}
		}
		this.previousSearchText = this.searchField.val();
	}

	getSearchResults() {
		$.getJSON('http://localhost:3000/wp-json/wp/v2/posts?search=' + this.searchField.val(), function (posts) {
			alert(posts[0].title.rendered);
		});

		//this.searchResults.html("Search results will appear here...");
		this.isSpinnerVisible = false;
	}
}
export default Search;