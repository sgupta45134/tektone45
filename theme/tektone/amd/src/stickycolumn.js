define([
    'jquery'
], function ($) {
	$.fn.stickyColumn = function (options) {
		var defaults = { columns: 1 };
		var settings = $.extend({}, defaults, options);
		var tables = this;


		return tables.each(function (tableIndex, table) {
			var test = settings;
			var rows = $(table).find('tbody > tr');
			var positionOfMainTable = $(table).find('tbody tr td:first-child').offset();

			var stickyColumn = $('<table id="sticky_column" class="alter" style="position: absolute; display: none;">');
			$('body').append(stickyColumn);
			stickyColumn.css('top', positionOfMainTable.top);
			stickyColumn.css('left', positionOfMainTable.left);

			$.each(rows, function (rowIndex, rowRunner) {
				var originalDayCells = $(rowRunner).find('td');

				var newRow = $('<tr>');
				var rowCells = $(rowRunner).children();
				for (var cellIndex = 0; cellIndex < test.columns; cellIndex++) {
					var clonedDayCell = $(rowCells[cellIndex]).clone();
					clonedDayCell.css('height', $(rowCells[0]).height());
					clonedDayCell.css('width', $(rowCells[cellIndex]).width());
					clonedDayCell.addClass('sticky_cell');
					newRow.append(clonedDayCell);
				}

				stickyColumn.append(newRow);
			});

			$(document).scroll(function () {
				var scrollposition = $(window).scrollLeft();

				if (scrollposition > 50) {
					var positionOfMainTable = $(table).find('tbody tr td:first-child').offset();
					stickyColumn.css('top', positionOfMainTable.top);
					stickyColumn.css('left', scrollposition);

					stickyColumn.show();
				}
				else {
					stickyColumn.hide();
				}
			});
		});
	};
});