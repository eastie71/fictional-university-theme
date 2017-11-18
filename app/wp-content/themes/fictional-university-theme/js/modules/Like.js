import $ from 'jquery';

class Like {
	constructor() {
		this.events();
	}

	events() {
		$('.like-box').on("click", this.clickDespatcher.bind(this))
	}

	// Methods here...
	clickDespatcher(e) {
		// Make sure we are pointing to the relevant like-box 
		// This code sets the var equal to closest like-box that was clicked.
		var currentLikeBox = $(e.target).closest(".like-box");

		// Always use the attr method to pull in newly loaded attributes
		if (currentLikeBox.attr("data-exists") == 'yes') {
			this.removeLike(currentLikeBox);
		} else {
			this.createLike(currentLikeBox);
		}
	}

	createLike(currentLikeBox) {
		$.ajax({
			// Set the nonce for WP to authorize the update.
			beforeSend: (xhr) => {
				xhr.setRequestHeader('X-WP-Nonce', universityData.nonce);
			},
			url: universityData.root_url + '/wp-json/university/v1/manageLike',
			type: 'POST',
			data: {'professor_id' : currentLikeBox.data('prof-id')},
			success: (response) => {
				currentLikeBox.attr('data-exists', 'yes');
				var likeCount = parseInt(currentLikeBox.find(".like-count").html(), 10);
				likeCount++;
				currentLikeBox.find(".like-count").html(likeCount);
				// Set the data-like attribute with the newly created like ID
				currentLikeBox.attr("data-like", response);
				console.log(response);
			},
			error: (response) => {
				console.log(response);
			}
		});
	}

	removeLike(currentLikeBox) {
		$.ajax({
			// Set the nonce for WP to authorize the update.
			beforeSend: (xhr) => {
				xhr.setRequestHeader('X-WP-Nonce', universityData.nonce);
			},
			url: universityData.root_url + '/wp-json/university/v1/manageLike',
			type: 'DELETE',
			data: {'like' : currentLikeBox.attr('data-like')},
			success: (response) => {
				currentLikeBox.attr('data-exists', 'no');
				var likeCount = parseInt(currentLikeBox.find(".like-count").html(), 10);
				likeCount--;
				currentLikeBox.find(".like-count").html(likeCount);
				// Clear the data-like attribute for the deleted like ID
				currentLikeBox.attr("data-like", '');
				console.log(response);
			},
			error: (response) => {
				console.log(response);
			}
		});
	}
}

export default Like;