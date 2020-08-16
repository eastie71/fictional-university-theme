import axios from "axios"

class MyNotes {
	constructor() {
		// Only run this javascript - if and only if there is a #my-notes element
		if (document.querySelector("#my-notes")) {
		  	// Set-up the header nonce once for ALL server requests
		  	axios.defaults.headers.common["X-WP-Nonce"] = universityData.nonce
		  	this.myNotes = document.querySelector("#my-notes")
		  	this.events()
		}
	}

	events() {
		// If you click ANYWHERE inside the #my-notes ul, then add the event listeners and sort out what to do in the actual listener
		this.myNotes.addEventListener("click", e => this.clickHandler(e))
		document.querySelector(".submit-note").addEventListener("click", () => this.createNote())
	}

	clickHandler(e) {
		if (e.target.classList.contains("delete-note") || e.target.classList.contains("fa-trash-o")) 
		  	this.deleteNote(e)
		if (e.target.classList.contains("edit-note") || e.target.classList.contains("fa-pencil") || e.target.classList.contains("fa-times")) 
		  	this.editNote(e)
		if (e.target.classList.contains("update-note") || e.target.classList.contains("fa-arrow-right")) 
		  	this.updateNote(e)
	}

	findNearestParentLi(el) {
		let thisNote = el
		while (thisNote.tagName != "LI") {
		  	thisNote = thisNote.parentElement
		}
		return thisNote
	}

	// Actual listener methods here...
	async deleteNote(e) {
		const thisNote = this.findNearestParentLi(e.target)
		try {
			const response = await axios.delete(universityData.root_url + "/wp-json/wp/v2/note/" + thisNote.getAttribute("data-id"))
			
			// Some tricky Brad Schiff manipulation here - to obtain a "slideOut" effect of jQuery
			thisNote.style.height = `${thisNote.offsetHeight}px`
			setTimeout(function () {
				thisNote.classList.add("fade-out")
			}, 20)
			setTimeout(function () {
				thisNote.remove()
			}, 401)

			// Remove the note limit message - as we just deleted a note
			document.querySelector(".note-limit-message").classList.remove("active")
		} catch (e) {
			console.log("Delete Note FAILED!")
		}
	}

	async updateNote(e) {
		const thisNote = this.findNearestParentLi(e.target)
	
		var theUpdatedNote = {
			"title": thisNote.querySelector(".note-title-field").value,
			"content": thisNote.querySelector(".note-body-field").value
		}
	
		try {
			const response = await axios.post(universityData.root_url + "/wp-json/wp/v2/note/" + thisNote.getAttribute("data-id"), theUpdatedNote)
			this.makeNoteReadOnly(thisNote)
		} catch (e) {
			console.log("Update Note FAILED!")
		}
	}

	async createNote() {
		var theNewNote = {
			"title": document.querySelector(".new-note-title").value,
			"content": document.querySelector(".new-note-body").value,
			// Server side (functions.php) will set these notes to "private" status
			"status": "publish"
		}
	
		try {
			const response = await axios.post(universityData.root_url + "/wp-json/wp/v2/note/", theNewNote)
	
			// Returns status of 201 if successfully created
		  	if (response.status === 201) {
				// clear the create new note fields
				document.querySelector(".new-note-title").value = ""
				document.querySelector(".new-note-body").value = ""
				// For new note Prepend it to the my-notes list elements - hide it first, and then "slide down" note to appear
				document.querySelector("#my-notes").insertAdjacentHTML(
					"afterbegin",
					` <li data-id="${response.data.id}" class="fade-in-calc">
						<input readonly class="note-title-field" value="${response.data.title.raw}">
						<span class="edit-note"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</span>
						<span class="delete-note"><i class="fa fa-trash-o" aria-hidden="true"></i> Delete</span>
						<textarea readonly class="note-body-field">${response.data.content.raw}</textarea>
						<span class="update-note btn btn--blue btn--small"><i class="fa fa-arrow-right" aria-hidden="true"></i> Save</span>
					</li>`
				)

				// Some more Brad Schiff magic here to make the new notes appear in a transition...

				// notice in the above HTML for the new <li> I gave it a class of fade-in-calc which will make it invisible temporarily so we can count its natural height
				let finalHeight // browser needs a specific height to transition to, you can't transition to 'auto' height
				let newlyCreated = document.querySelector("#my-notes li")
	
				// give the browser 30 milliseconds to have the invisible element added to the DOM before moving on
				setTimeout(function () {
			  		finalHeight = `${newlyCreated.offsetHeight}px`
			  		newlyCreated.style.height = "0px"
				}, 30)
	
				// give the browser another 20 milliseconds to count the height of the invisible element before moving on
				setTimeout(function () {
			  		newlyCreated.classList.remove("fade-in-calc")
			  		newlyCreated.style.height = finalHeight
				}, 50)
	
				// wait the duration of the CSS transition before removing the hardcoded calculated height from the element so that our design is responsive once again
				setTimeout(function () {
			  		newlyCreated.style.removeProperty("height")
				}, 450)
		  	} else {
				if (response.data) {
					document.querySelector(".note-limit-message").innerHTML = response.data
					document.querySelector(".note-limit-message").classList.add("active")
				}
				console.log("Create Note FAILED!");
				console.log(response);
		  	}
		} catch (e) {
		  	console.error(e)
		}
	}

	editNote(e) {
		const thisNote = this.findNearestParentLi(e.target)
	
		if (thisNote.getAttribute("data-state") == "editable") {
			// switch to readonly (ie. edit button enabled)
		  	this.makeNoteReadOnly(thisNote)
		} else {
			// switch to editable (ie. cancel button enabled)
		  	this.makeNoteEditable(thisNote)
		}
	}

	makeNoteEditable(thisNote) {
		// If editing, then change the Edit button to a Cancel button
		thisNote.querySelector(".edit-note").innerHTML = '<i class="fa fa-times" aria-hidden="true"></i> Cancel'
		thisNote.querySelector(".note-title-field").removeAttribute("readonly")
		thisNote.querySelector(".note-body-field").removeAttribute("readonly")
		thisNote.querySelector(".note-title-field").classList.add("note-active-field")
		thisNote.querySelector(".note-body-field").classList.add("note-active-field")
		thisNote.querySelector(".update-note").classList.add("update-note--visible")
		thisNote.setAttribute("data-state", "editable")
	}

	makeNoteReadOnly(thisNote) {
		// If canceling, then change the Cancel button back to an Edit button
		thisNote.querySelector(".edit-note").innerHTML = '<i class="fa fa-pencil" aria-hidden="true"></i> Edit'
		thisNote.querySelector(".note-title-field").setAttribute("readonly", "true")
		thisNote.querySelector(".note-body-field").setAttribute("readonly", "true")
		thisNote.querySelector(".note-title-field").classList.remove("note-active-field")
		thisNote.querySelector(".note-body-field").classList.remove("note-active-field")
		thisNote.querySelector(".update-note").classList.remove("update-note--visible")
		thisNote.setAttribute("data-state", "cancel")
	}
}

export default MyNotes;