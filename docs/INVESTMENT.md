# Step 10 — Investment inputs

Three screens under the **Investment** tab group of a study:

| Screen | Address | What it holds |
|---|---|---|
| Land & construction | `/studies/{study}/land-acquisition-costs` | Land purchase · hard construction · soft construction · funding · loans (land, hard cost) |
| Property acquisition | `/studies/{study}/property-acquisition-costs` | Purchase of an existing property · cost breakdown (Land / Building / FF&E) · replacement · loan |
| FF&E cost | `/studies/{study}/ffe-cost` | Up to 10 FF&E items · execution dates · payment terms · equity funding · loan |

Route names: `studies.investment.land|property|ffe.edit` and `.update`.

## Where the data goes (same tables as the old app)
`acquisitions` (one per study) · `property_acquisitions` + `property_acquisition_breakdowns` · `ffe` + `ffe_items` · `loans`
(`section_name`: land, hard_cost_construction, property_loan, ffe_cost). The calculation is unchanged; it reads the same rows.

## Rules
- Down payment ≤ 100 %; the two balance payments plus the down payment add up to 100 % (second balance is computed).
- Non-installment purchases store first/second/after/interval as 0, as the old app did.
- Property cost breakdown must total 100 %. FF&E item names are unique; a blank name deletes the row.
- Construction / FF&E end date may not pass the operation start (old checks, now shown as validation messages).
- Loan: pricing = base + margin. Grace period only for grace types; step rate/interval only for step types (the rest are saved as 0).
- Interval words keep the old spelling "quartly" because the calculation reads them.

## Deliberate differences from the old app
- Blank hard-construction equity defaults to 100 % (old default 0).
- Property breakdown must total 100 %.
- Fields a loan type does not use are zeroed on save.
- Property uses `has_land_section` = 1.
- Arabic wording is new and needs your review.

## Checked against the old app
Studies 306, 307, 313, 314: inputs re-saved through the new screens, results recalculated, compared with the old results —
12,886 / 4,773 / 12,886 / 4,132 numbers, **0 differences**. Only *normal* loans exist in this data.
**Not verified:** step-up/step-down/grace loan types, and quarterly or semi-annual installments. A made-up test with
those could not run in the old application itself (it stopped with an error in its own loan calculation), so there is no old result to compare.

## Installments of a purchase (land or property)
A purchase paid by installments is: the first down payment at the purchase month, the second one `after` months later, then `count` equal installments, one every
`interval` after the second down payment (`InstallmentMethod`). The loan (if the equity funding is below 100 %) starts drawing when the equity is used up
(`calculateLandLoanWithdrawal`); the payable of the purchase (`FinancialReports`) is worked out for every month; the interest that builds up between the
withdrawals is part of the cost of the land / property (`CalculateFixedLoanAtEndService`). Tests: `InstallmentPaymentsTest`, `BalanceSheetCheckTest`
(Check Error 0 in every month for land and property bought by installments months apart, with loans, and with an opening stock).
