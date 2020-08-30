//import $ from 'jquery';
import axios from "axios"

class Search {
	// 1) Describe and initiate the object
	constructor() {
		// append the HTML associated with the Search overlay to the end of the html body
		this.addSearchHTML();

		this.openButton = document.querySelectorAll(".js-search-trigger");
		this.closeButton = document.querySelector(".search-overlay__close");
		this.searchOverlay = document.querySelector(".search-overlay");
		this.searchField = document.querySelector("#search-term");
		this.searchResults = document.querySelector("#search-overlay__results");
		this.events();
		this.isSearchOverlayOpen = false;
		this.isSpinnerVisible = false;
		this.previousSearchText;
		this.typingTimer;
	}

	// 2) Events
	events() {
		this.openButton.forEach(el => {
			el.addEventListener("click", e => {
				e.preventDefault()
			  	this.openOverlay()
			})
		})
		this.closeButton.addEventListener("click", () => this.closeOverlay());
		document.addEventListener("keydown", e => this.keyPressHandler(e));
		this.searchField.addEventListener("keyup", () => this.searchText());
	}
	
	// 3) Methods (function, action...)
	openOverlay() {
		this.searchOverlay.classList.add("search-overlay--active");
		// remove the scrolling of the page when search overlay modal opens
		document.body.classList.add("body-no-scroll");
		// Clear the search field of any previous search entry
		this.searchField.value = "";
		// Set the focus on to the search field after 301 miliseconds (allowing for overlay to load)
		// Shorthand code here for an anonymous function using ES6 arrow function
		setTimeout( () => this.searchField.focus(), 301);
		this.isSearchOverlayOpen = true;
		// by returning false this prevents the "a" tag call to the link if js is enabled
		return false;
	}

	closeOverlay() {
		this.searchOverlay.classList.remove("search-overlay--active");
		// re-enable the scrolling of the page when search overlay modal is closed
		document.body.classList.remove("body-no-scroll");
		this.isSearchOverlayOpen = false;
	}

	keyPressHandler(e) {
		// Open the Search window if 's' key is pressed and NOT inside a text input field
		if (e.keyCode == 83 && !this.isSearchOverlayOpen && document.activeElement.tagName != "INPUT" && document.activeElement.tagName != "TEXTAREA") {
			this.openOverlay();
		// Close the search window if ESC key is pressed
		} else if (e.keyCode == 27 && this.isSearchOverlayOpen) {
			this.closeOverlay();
		}
	}

	searchText() {
		if (this.searchField.value != this.previousSearchText) {
			clearTimeout(this.typingTimer);
			if (this.searchField.value) {
				if (!this.isSpinnerVisible) {
					this.searchResults.innerHTML = '<div class="spinner-loader"></div>';
					this.isSpinnerVisible = true;
				}
				this.typingTimer = setTimeout(this.getSearchResults.bind(this), 750);
			} else {
				this.searchResults.innerHTML = "";
				this.isSpinnerVisible = false;
			}
		}
		this.previousSearchText = this.searchField.value;
	}

	async getSearchResults() {
		try {
			const response = await axios.get(universityData.root_url + '/wp-json/university/v1/search?data=' + this.searchField.value);
			const results = response.data;

			// Below is using 'template literals' (``) for creating HTML, and anything within ${} is Javascript
			// Cannot perform "if conditions" inside ${}, but can use ternary operator
			// It checks if any results (posts) are found and displays the title with a link for each post found, 
			// otherwise reports no results found
			this.searchResults.innerHTML = `
				<div class="row">
					<div class="one-third">
						<h2 class="search-overlay__section-title">General Information</h2>
						${results.generalInfo.length ? '<ul class="link-list min-list">' : '<p>No General Results Found.</p>'}
							${results.generalInfo.map(result => `<li><a href='${result.permalink}'>${result.title}</a> ${result.type == 'post' ? `by ${result.authorName}` : ''} </li>`).join('')}
						${results.generalInfo.length ? '</ul>' : ''}
					</div>
					<div class="one-third">
						<h2 class="search-overlay__section-title">Programs</h2>
						${results.programs.length ? '<ul class="link-list min-list">' : `<p>No Programs Found. <a href="${universityData.root_url}/programs">View All Programs</a></p>`}
							${results.programs.map(result => `<li><a href='${result.permalink}'>${result.title}</a></li>`).join('')}
						${results.programs.length ? '</ul>' : ''}
						
						<h2 class="search-overlay__section-title">Professors</h2>
						${results.professors.length ? '<ul class="professor-cards">' : '<p>No Professors Found.</p>'}
							${results.professors.map(result => `
								<li class="professor-card__list-item">
									<a class="professor-card" href="${result.permalink}">
										<img class="professor-card__image" src="${result.image}">
										<span class="professor-card__name">${result.title}</span>
									</a>
								</li>
							`).join('')}
						${results.professors.length ? '</ul>' : ''}
					</div>
					<div class="one-third">
						<h2 class="search-overlay__section-title">Campuses</h2>
						${results.campuses.length ? '<ul class="link-list min-list">' : `<p>No Campuses Found. <a href="${universityData.root_url}/campuses">View All Campusess</a></p>`}
							${results.campuses.map(result => `<li><a href='${result.permalink}'>${result.title}</a></li>`).join('')}				
						${results.campuses.length ? '</ul>' : ''}
						
						<h2 class="search-overlay__section-title">Events</h2>
						${results.events.length ? '' : `<p>No Events Found. <a href="${universityData.root_url}/events">View All Events</a></p>`}
							${results.events.map(result => `
								<div class="event-summary">
									<a class="event-summary__date t-center" href="${result.permalink}">
										<span class="event-summary__month">${result.month}</span>
										<span class="event-summary__day">${result.day}</span>  
									</a>
									<div class="event-summary__content">
										<h5 class="event-summary__title headline headline--tiny"><a href="${result.permalink}">${result.title}</a></h5>
										<p>
											${result.excerpt}
											<a href="${result.permalink}" class="nu gray"> Learn more</a>
										</p>
									</div>
								</div>
							`).join('')}				
		
					</div>
				</div>
			`;
			this.isSpinnerVisible = false;
		} catch(e) {
			console.log(e);
		}
	
	}

	addSearchHTML() {
		document.body.insertAdjacentHTML(
			"beforeend",
			`
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