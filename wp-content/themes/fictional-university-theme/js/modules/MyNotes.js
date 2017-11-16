import $ from 'jquery';

class MyNotes {
	constructor() {
		this.events();
	}

	events() {
		$(".delete-note").on("click", this.deleteNote);
		$(".edit-note").on("click", this.editNote);
	}

	// Methods here...
	deleteNote(e) {
		var this_note = $(e.target).parents("li");
		$.ajax({
			// Set the nonce for WP to authorize the deletion.
			beforeSend: (xhr) => {
				xhr.setRequestHeader('X-WP-Nonce', universityData.nonce);
			},
			url: universityData.root_url + '/wp-json/wp/v2/note/' + this_note.data('id'),
			type: 'DELETE',
			success: (response) => {
				// jquery command to delete element from page a slide up in nice fashion...
				this_note.slideUp();
				console.log("Delete Note is good!");
				console.log(response);
			},
			error: (response) => {
				console.log("Delete Note FAILED!");
				console.log(response);
			}
		});
	}

	editNote(e) {
		var this_note = $(e.target).parents("li");
		this_note.find(".note-title-field, .note-body-field").removeAttr("readonly").addClass("note-active-field");
		this_note.find(".update-note").addClass("update-note--visible")
	}
}

export default MyNotes;