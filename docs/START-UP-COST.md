# Step 09D — Start-up cost

Screen: `/studies/{study}/start-up-cost` (last tab of the Expenses group). Two tables, three lines each; a line is used when it has a name.

1. **Start-up Cost** — Expense Item Name, Currency, Cost Amount, Date (a month from the study start to the opening month),
   Cash Payment % (Deferred Payment % = 100 − cash, shown live), Due Days (0, 15, 30 … 180).
2. **Pre-operating Expense** — Expense Name, Currency and an amount for every month from the study start to the month before the opening
   (with "copy to the following months" on each cell).

Stored as the old app stored it, in `department_expenses` with `model_name` = `start`:
section `start_up_cost` (`start_up_cost`, `date` = date index, `cash_payment_percentage`, `deferred_payment_percentage`, `due_days`, `chosen_currency`)
and section `pre_operating_expense` (`chosen_currency`, `manpower_payload` = amount by date index).
The calculation (`calculateStartUpCost`, `calculatePreOperatingExpense`) reads the rows unchanged.

Rules: a named line must be complete; cost and amounts ≥ 0; cash 0–100; due days from the list; the month must be one of the offered months;
currencies are the study's own; a name may not repeat in a table. Lines keep their id when saved again; clearing a name removes the line.
Differences from the old app: none in what is stored. The old month picker is a list of months here.
If the study opens in its first month there are no months before the opening and the pre-operating table says so.
Tests: `tests/Feature/StartUpCostTest.php` (7, PHP), `tests/js/start-up-cost.spec.js` (5).
