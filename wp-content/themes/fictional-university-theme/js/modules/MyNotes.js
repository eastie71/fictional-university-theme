import $ from 'jquery';

class MyNotes {
	constructor() {
		this.events();
	}

	events() {
		// if you click anywhere within the #my-notes ul, AND it matches the interior element (eg. ".delete-note") then set the callback function
		$("#my-notes").on("click", ".delete-note", this.deleteNote);
		$("#my-notes").on("click", ".edit-note", this.editNote.bind(this));
		$("#my-notes").on("click", ".update-note", this.updateNote.bind(this));
		$(".submit-note").on("click", this.createNote.bind(this));
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

	createNote(e) {
		var theNewNote = {
			'title': $(".new-note-title").val(),
			'content': $(".new-note-body").val(),
			'status': 'publish'
		}
		$.ajax({
			// Set the nonce for WP to authorize the update.
			beforeSend: (xhr) => {
				xhr.setRequestHeader('X-WP-Nonce', universityData.nonce);
			},
			url: universityData.root_url + '/wp-json/wp/v2/note/',
			type: 'POST',
			data: theNewNote,
			success: (response) => {
				// clear the create new note fields
				$(".new-note-title, .new-note-body").val('');
				// For new note Prepend it to the my-notes list elements - hide it first, and then "slide down" note to appear
				$(`
					<li data-id="${response.id}">
						<input readonly class="note-title-field" type="text" value="${response.title.raw}">
						<span class="edit-note"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</span>
						<span class="delete-note"><i class="fa fa-trash-o" aria-hidden="true"></i> Delete</span>
						<textarea readonly class="note-body-field">${response.content.raw}</textarea>
						<span class="update-note btn btn--blue btn--small"><i class="fa fa-arrow-right" aria-hidden="true"></i> Save</span>
					</li>
				`).prependTo('#my-notes').hide().slideDown();
				console.log("Create Note is good!");
				console.log(response);
			},
			error: (response) => {
				console.log("Create Note FAILED!");
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