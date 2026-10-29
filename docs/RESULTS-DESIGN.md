# Results pages design (after step 12)

The Revenue results and Expense results pages now use the same look as the financial statements:
coloured band, four key-figure cards (total, best year, yearly average, growth or start-up cost), heat map and compact-rows options,
year header band, sticky name column, colour bar and trend line for each line, lit column on hover, sum column, copy and CSV.

Shared parts: `Components/Reports/ResultsHead.vue` (band, cards, options) and `Components/Reports/ResultsTable.vue` (the table).
Styling: section 22 of `resources/css/design/revenue.css`. No calculation or field name was changed.
Next (second zip): the input pages (Revenue, Expenses, Investment) get the same header, colours and row styling without changing how they are typed into.

## Round 2: input tables
All input tables of the Revenue, Expenses and Investment pages (classes rv-grid, ex-grid) share the statement look: coloured year header, banded rows, accented name column, highlighted total rows (CSS section 23 of revenue.css). Typing and calculations are unchanged.
