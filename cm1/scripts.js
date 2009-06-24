function toggleAuthMode() {
	var radioImplicit = document.getElementById("auth_mode_implicit");
	var authenticationContainer = document.getElementById("authentication_explicit");
	if (!radioImplicit || !authenticationContainer)
		return;
	if (radioImplicit.checked) {
		authenticationContainer.style.display = "none";
	} else {
		authenticationContainer.style.display = "block";
		document.getElementById("username").focus();
	}
}
function scroll(obj) {
	var file1 = document.getElementById("file1");
	var file2 = document.getElementById("file2");
	if (obj == file1) {
		file2.scrollTop = obj.scrollTop;
		file2.scrollLeft = obj.scrollLeft;
	} else {
		file1.scrollTop = obj.scrollTop;
		file1.scrollLeft = obj.scrollLeft;
	}
}
function diff(path, workingCopyUid) {
	var diffUrl = "index.php?path=" + path + "&wc=" + workingCopyUid + "&cmd=diff";
	var diffWindow = window.open(diffUrl, "diff", "width=770,height=700,resizable=yes");
	diffWindow.focus();
}

function toggleCheckbox(checkboxId) {
	var checkbox = $(checkboxId);
	checkbox.checked = !checkbox.checked;
}

function toggleCheckboxes(checked, containerId) {
	$$('#' + containerId + ' input').each(function(item) {
		item.checked = checked;
	});
}
function toggleMasterCheckbox(checkboxId, containerId) {
	var hasUncheckedItems = false;
	$$('#' + containerId + ' input').each(function(item) {
		if (!item.checked && item.id != 'select_all') {
			hasUncheckedItems = true;
		}
	});
	$(checkboxId).checked = !hasUncheckedItems;
}