var lines;
var modifications;

var lineHeight;
var cursorHeight;
var bodyHeight;
var canvasHeight;
var visibleLines = 0;
var modificationHeight;
var focusedFile;

Event.observe(window, 'load', function() {
	Event.observe(window, 'resize', calculateGUI);
	if (Prototype.Browser.IE || Prototype.Browser.Gecko) {
		Event.observe('file1', 'scroll', positionCursor);
		Event.observe('file2', 'scroll', positionCursor);
	}
	Event.observe('bar', 'click', scrollDiff);

	if (modifications.length < 1) {
		$('filesIdentical').show();
	}
	insertModifications();
	calculateGUI();
});

function insertModifications() {
	modifications.each(function(modification, index) {
		var className = 'mod ' + modification.type;
		var id = 'mod' + index;
		var mod = new Element('div', { id: id, 'class': className});
		$('bar').insert(mod);
	});
}
function calculateGUI() {
	var body = Element.extend(document.body);
	bodyHeight = body.getHeight();
	headerHeight = $('header').getHeight() + $('diffHeader').getHeight() + 36;
	canvasHeight = bodyHeight - headerHeight - 20;

	$('file1').style.height = canvasHeight + 'px';
	$('file2').style.height = canvasHeight + 'px';
	$('bar').style.height = canvasHeight + 'px';

	var tr = $('file1').firstDescendant().firstDescendant().firstDescendant();
	lineHeight = tr.getHeight();

	visibleLines = Math.ceil(lines / (canvasHeight / lineHeight));
	cursorHeight = Math.floor(canvasHeight / visibleLines);
	$('cursor').style.height = cursorHeight + 'px';

	modificationHeight = Math.ceil(canvasHeight / lines);
	modifications.each(function(modification, index) {
		var mod = $('mod' + index);
		mod.style.height = modificationHeight + 'px';
		var modY = (modification.line - 1) * (canvasHeight / lines);
		if (modY < 0) {
			modY = 0;
		}
		mod.style.top = modY + 'px';
	});
}
function positionCursor(event) {
	var currentFileId, otherFileId;
	if (event.target.id == 'file1') {
		currentFileId = 'file1';
		otherFileId = 'file2';
	} else {
		currentFileId = 'file2';
		otherFileId = 'file1';
	}
	var currentScrollTop = $(currentFileId).scrollTop;
	var currentScrollLeft = $(currentFileId).scrollLeft;

	$(otherFileId).scrollTop = currentScrollTop;
	$(otherFileId).scrollLeft = currentScrollLeft;

	var cursorY = (Math.floor(currentScrollTop/lineHeight)/lines) * canvasHeight - 1;
	$('cursor').style.top = cursorY + 'px';

	$('filesIdentical').hide();
}

function scrollDiff(event) {
	var relativePointerY = Event.pointerY(event) - headerHeight;
	var line = Math.ceil((relativePointerY / canvasHeight) * lines);
	line -= Math.round(visibleLines / 2);
	if (line < 1) {
		line = 1;
	}
	scrollToLine(line);
}
function scrollToLine(line) {
	$('file1').scrollTop = (line-1) * lineHeight;
}