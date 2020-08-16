import axios from "axios"

class Like {
	constructor() {
		if (document.querySelector(".like-box")) {
			// Set the nonce for WP to authorize the update.
			axios.defaults.headers.common["X-WP-Nonce"] = universityData.nonce
			this.events()
		}
	}

	events() {
		document.querySelector(".like-box").addEventListener("click", e => this.clickDispatcher(e))
	}

	// Methods here...
	clickDispatcher(e) {
		let currentLikeBox = e.target
		// Make sure we are pointing to the relevant like-box (in case we have more than one like-box)
		// This code sets the var equal to closest like-box that was clicked.
		while (!currentLikeBox.classList.contains("like-box")) {
		  	currentLikeBox = currentLikeBox.parentElement
		}
	
		// Always use the attr method to pull in newly loaded attributes.
		if (currentLikeBox.getAttribute("data-exists") == "yes") {
		  	this.removeLike(currentLikeBox)
		} else {
		  	this.createLike(currentLikeBox)
		}
	}

	async createLike(currentLikeBox) {
		try {
			// Effectively this is adding the argument to the URL ie. manageLike?professor_id=123
			const response = await axios.post(universityData.root_url + "/wp-json/university/v1/manageLike", 
												{ "professor_id": currentLikeBox.getAttribute("data-prof-id") })
			// Returns true if successfully created
			console.log(response.data)
			if (response.data.success === true) {
				currentLikeBox.setAttribute("data-exists", "yes")
				var likeCount = parseInt(currentLikeBox.querySelector(".like-count").innerHTML, 10)
				likeCount++
				currentLikeBox.querySelector(".like-count").innerHTML = likeCount
				// Set the data-like attribute with the newly created like ID
				currentLikeBox.setAttribute("data-like", response.data.id)
			}
		} catch (e) {
		  	console.log("Failed to Add Like for this Professor")
		}
	}

	async removeLike(currentLikeBox) {
		try {
			const response = await axios.delete(universityData.root_url + "/wp-json/university/v1/manageLike", { data: { "like": currentLikeBox.getAttribute("data-like") } })
			// Returns true if successfully deleted
			if (response.data.success == true) {
				currentLikeBox.setAttribute("data-exists", "no")
				var likeCount = parseInt(currentLikeBox.querySelector(".like-count").innerHTML, 10)
				likeCount--
				currentLikeBox.querySelector(".like-count").innerHTML = likeCount
				// Reset the data-like attribute to empty (not liked)
				currentLikeBox.setAttribute("data-like", "")
			}
			console.log(response.data)
		} catch (e) {
		  	console.log("Failed to Remove Like for this Professor")
		}
	}
}

export default Like;