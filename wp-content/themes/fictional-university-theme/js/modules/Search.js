import $ from 'jquery';

class Search {
	// 1) Describe and initiate the object
	constructor() {
		// append the HTML associated with the Search overlay to the end of the html body
		this.addSearchHTML();

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
		// Clear the search field of any previous search entry
		this.searchField.val('');
		// Set the focus on to the search field after 301 miliseconds (allowing for overlay to load)
		// Shorthand code here for an anonymous function using ES6 arrow function
		setTimeout( () => this.searchField.focus(), 301);
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
		if (e.keyCode == 83 && !this.isSearchOverlayOpen && !$("input, textarea").is(':focus')) {
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
				this.typingTimer = setTimeout(this.getSearchResults.bind(this), 750);
			} else {
				this.searchResults.html('');
				this.isSpinnerVisible = false;
			}
		}
		this.previousSearchText = this.searchField.val();
	}

	getSearchResults() {
		// posts => is equivalent to function(posts) {}.bind()
		$.getJSON(universityData.root_url + '/wp-json/wp/v2/posts?search=' + this.searchField.val(), posts => {
			//alert(posts[0].title.rendered);
			// Below is using 'template literals' for creating HTML, and anything within ${} is Javascript
			// Cannot perform "if conditions" inside ${}, but can use ternary operator
			// It checks if any results (posts) are found and displays the title with a link for each post found, 
			// otherwise reports no results found
			this.searchResults.html(`
				<h2 class="search-overlay__section-title">General Information</h2>
				${posts.length ? '<ul class="link-list min-list">' : '<p>No Search Results Found.</p>'}
					${posts.map(post => `<li><a href='${post.link}'>${post.title.rendered}</a></li>`).join('')}
				${posts.length ? '</ul>' : ''}
			`);
			this.isSpinnerVisible = false;
		});

		//this.searchResults.html("Search results will appear here...");
		
	}

	addSearchHTML() {
		$("body").append(`
			<div class="search-overlay">
			  <div class="search-overlay__top">
			    <div class="container">
			      <i class="fa fa-search search-overlay__icon" aria-hidden="true"></i>
			      <input id="search-term" type="text" class="search-term" placeholder="Enter your search..." autofocus="true">
			      <i class="fa fa-window-close search-overlay__close" aria-hidden="true"></i>
			    </div>
			  </div>
			  <div class="container">
			    <div id="search-overlay__results"></div>
			  </div>
			</div>
		`);
	}
}
export default Search;