import $ from 'jquery';

class MyNotes {
	constructor() {
		this.events();
	}

	events() {
		$(".delete-note").on("click", this.deleteNote);
		$(".edit-note").on("click", this.editNote.bind(this));
		$(".update-note").on("click", this.updateNote.bind(this));
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

	updateNote(e) {
		var this_note = $(e.target).parents("li");
		var theUpdatedNote = {
			'title': this_note.find(".note-title-field").val(),
			'content': this_note.find(".note-body-field").val()
		}
		$.ajax({
			// Set the nonce for WP to authorize the update.
			beforeSend: (xhr) => {
				xhr.setRequestHeader('X-WP-Nonce', universityData.nonce);
			},
			url: universityData.root_url + '/wp-json/wp/v2/note/' + this_note.data('id'),
			type: 'POST',
			data: theUpdatedNote,
			success: (response) => {
				this.makeNoteReadOnly(this_note);
				console.log("Update Note is good!");
				console.log(response);
			},
			error: (response) => {
				console.log("Update Note FAILED!");
				console.log(response);
			}
		});
	}

	editNote(e) {
		var this_note = $(e.target).parents("li");

		if (this_note.data("state") == "editable") {
			// switch to readonly (ie. edit)
			this.makeNoteReadOnly(this_note);
		} else {
			// switch to editable (ie. cancel)
			this.makeNoteEditable(this_note);
		}
	}

	makeNoteEditable(this_note) {
		// If editing, then change the Edit button to a Cancel button
		this_note.find(".edit-note").html('<i class="fa fa-times" aria-hidden="true"></i> Cancel');
		this_note.find(".note-title-field, .note-body-field").removeAttr("readonly").addClass("note-active-field");
		this_note.find(".update-note").addClass("update-note--visible");
		this_note.data("state", "editable");
	}

	makeNoteReadOnly(this_note) {
		// If canceling, then change the Cancel button to a Edit button
		this_note.find(".edit-note").html('<i class="fa fa-pencil" aria-hidden="true"></i> Edit');
		this_note.find(".note-title-field, .note-body-field").attr("readonly", "readonly").removeClass("note-active-field");
		this_note.find(".update-note").removeClass("update-note--visible");
		this_note.data("state", "cancel");
	}
}

export default MyNotes;