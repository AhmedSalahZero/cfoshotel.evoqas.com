# Revenue inputs — Step 08A: Rooms and the company type lists

## What the user sees
- **Study menu** (tabs across the top of each page of a study): Study, Rooms. Foods, Gaming, Meetings and Other revenue are added by Steps 08B–08D. A green tick means the page was saved once.
- **Rooms page** (`/studies/{id}/rooms`), six sections:
  1. Rooms and rates — one currency for all rooms; per room type: ADR, escalation before opening, annual escalation. The **ADR at the opening date** is shown live and recalculated on the server when saving: ADR × (1 + escalation)^(years from study start to opening).
  2. Exchange rate per operating year — only when the study has an additional currency.
  3. Targeted occupancy — one rate for all rooms, or one per room type; one figure per operating year (0–100%).
  4. Seasonality — highest occupancy per month: flat 100%, or month by month; one pattern for all rooms or one per room type.
  5. Reservation channels (only when the study uses them) — share of revenue and discount per year. The shares must add up to 100% in every year.
  6. Collection — when the money comes in: regularly (monthly / quarterly / half-yearly / yearly) or customised (four payments, rates adding up to 100%, each due after 0–180 days). With channels: one policy for all, or one per channel.
- **Types page** (`/types`, company administrator only): the five lists (rooms, food and beverage, gaming, meetings, other revenue). Add, rename, remove. A type used by any study is locked and cannot be removed. The "Total ..." row is managed by the system.

## Rules
- Operating years are the study years from the opening month on; the first and last can be part years (shown as "10 months").
- A study that enters rooms as one **total** can only use "one rate for all rooms" for occupancy and seasonality.
- Seasonality is stored as percentages as typed (the old application's ×100 display quirk is not carried over).
- Storage stays compatible with the old application: year-keyed JSON, month-keyed JSON ("01".."12"), collection policy `{"rate":[..],"due_in_days":[..]}`.
- A total-mode room row stores the company's "Total ..." type id (the old application stored null).
- Defaults: seasonality flat 100%, general occupancy.
- Viewers get a locked page; a read-only company can look but not save; another company's study is 404.

## Where the code is
`app/Support/StudyYears.php`, `CollectionTerms.php`, `StudyMenu.php` · `app/Services/Revenue/*` · `app/Http/Controllers/Hospitality/*` · `app/Http/Requests/Revenue/*` · `resources/js/Pages/Studies/Rooms.vue`, `Pages/Company/Types.vue` · `resources/js/Components/Revenue/*` · `resources/css/design/revenue.css` (prefix `rv-`).

## Tests (all small, hand-checkable)
`tests/Unit/StudyYearsTest.php` · `tests/Feature/RoomsRevenueTest.php` · `tests/Feature/TypeListsTest.php` · `tests/js/revenue.spec.js` · `DesignSystemCssTest` now includes `revenue.css`.

---

# Step 08B — Food and Beverage

## What the user sees
- **Foods page** (`/studies/{id}/foods`), tab "Food and beverage" in the study menu. Number of facilities and seats come from the study (Step 07) and are shown, not edited here.
  1. **Facilities and projection method** — each facility picks one of four methods: guest capture % × value per guest · guest capture % × meals per guest × value per meal · covers per day × value of a cover · percentage of the rooms revenue. A link applies one facility's method to all.
  2. **Value of a cover** — currency, value, escalation before opening, annual escalation; the value at the opening date is shown live (same formula as the rooms ADR) and recalculated on the server.
  3. **Yearly figures** — only what the chosen method needs: guest capture (0–100%), meals per guest, covers per day, percent of rooms revenue (0–1000%).
  4. **Collection** — one policy for all facilities, or one per facility (a study that enters F&B as one total has the general policy only).

## Rules
- Only the yearly figures of the chosen method are saved; the other series are saved empty (as the old application did).
- Storage is compatible with the old application: `f&b_facilities`, year-keyed JSON for the four series, per-facility `collection_policy_*`, and `food_collection_policy_type` = `general_collection_terms` or `terms_per_sales_channel` (the old name for "per facility").
- A customised policy must total 100% — checked for the general policy, and for each facility only when the policy is per facility.
- Viewers, read-only companies and other companies: same behaviour as Rooms.

## Not in this part (on purpose)
The old screen also showed, as reading aids, the hotel's guest count and total rooms revenue per year. Those numbers come from the full revenue calculation (the study engine), which is a later step. Showing an approximation would risk wrong figures, so they are added when that engine is connected (dashboard step).

## Tests
`tests/Feature/FoodsRevenueTest.php` · `tests/js/foods.spec.js`

---

# Step 08C — Gaming and Meeting spaces

Both pages appear in the study menu only when the study uses the stream (they can be switched off in the study form). The number of facilities and their capacity come from the study and are shown, not edited.

## Gaming (`/studies/{id}/casinos`)
Each facility picks one of three methods: guest capture % × charges per guest · guests per day × charges per guest · percentage of the rooms revenue. Charges per guest have a currency, escalation before opening, annual escalation and a live "charges at the opening date". Collection terms as in Food and Beverage (general, or per facility).
- The two guest methods keep their yearly figure in the same column (`guest_capture_cover_percentage`), as the old application did; what it means depends on the method.
- A percentage of the rooms revenue needs no charges (saved as zero).

## Meeting spaces (`/studies/{id}/meetings`)
Each facility picks one of three methods: guests × charges per guest × occupancy % · facilities × daily rent × occupancy % · percentage of the food and beverage revenue.
- The per-guest charge is saved in `charges_value_per_guest`, the daily rent in `cover_value`; occupancy in `guest_capture_cover_percentage`; the percentage method in `percentage_from_f_and_b_revenues`.
- **Seasonality plans** spread a year's demand over its months. There is one plan for the per-guest method and one for the daily-rent method; a plan is shown only when a facility uses its method. A plan is general or per facility, and flat (equal months), quarterly (four shares) or monthly (twelve shares). **Quarterly and monthly shares must add up to 100%** (the old application normalised silently; the new one asks for a clean 100%).
- General plan: saved on the study (`guest_meeting_*`, `rent_meeting_*`). Per facility: saved on each facility (`guest_seasonality`, `rent_seasonality`). Quarter keys are `quarter-one` … `quarter-four`; months are `01` … `12`.
- A study that enters meetings as one total has general plans and the general collection policy only.

## Also in 08C
Food and Beverage now matches the old application on one point: when sales are a percentage of the rooms revenue, no cover value is asked for.

## Deliberate difference from the old application
When a facility's method is changed, the old application reset its charges and currency to zero. The new application keeps them, and only the figures of the new method are required. (Nothing is lost by switching back and forth.)

## Tests
`tests/Feature/GamingAndMeetingsRevenueTest.php` · `tests/js/gaming.spec.js` (plus the updated foods tests)

---

# Field names follow the reference project

Every label on the study form, Rooms, Food and Beverage, Gaming and Meeting spaces uses the name found on the matching page of the old application (for example *F&B Facility Count*, *Guest Capacity*, *Gaming Facility Types*, *Gaming Facility Count*, *Meeting Facility Count*, *Other Facility Count*, *Cover Value Escalation Rate %*). `tests/js/reference-names.spec.js` keeps them from drifting. The one deliberate change: the old pages spell "Percentage" as "Percenatge" and "Seasonality" as "Seaonality"; the new ones spell them correctly. Arabic was written for the new application (the reference has no Arabic wording for these pages) and needs your review.

---

# Step 08D — Other revenue (and the study menu is complete)

Page: `/studies/{id}/others`, shown in the study menu only when the study uses other revenue. The number of facilities (spa, parking, shops…) comes from the study and is only shown.

Each facility picks one of two methods:
- **Guest Capture % x Charges Per Guest Method** — saved in `f&b_facilities` (the old column name), the charges in `charges_per_guest`, escalation in `charges_per_guest_escalation_rate` / `..._annual_escalation_rate`, the figure at opening in `charges_per_guest_at_operation_date`, the currency in `chosen_other_currency`, the yearly capture in `guest_capture_cover_percentage`.
- **Percentage % From Rooms Revenues** — yearly percentage in `percentage_from_rooms_revenues`; no charges are asked for (saved as zero).

Collection terms work as in Gaming (general, or per facility); the mode is kept in `other_collection_policy_type` and the general policy in `others_general_collection_policy_*`. Saving sets `has_visit_other_section`.

The study menu now lists Study · Rooms · Food and beverage · Gaming · Meetings · Other revenue (each stream page only when the study uses it). Field names follow the reference page (*Other Revenues Facility Count*, *Charges Per Guest Escalation Rate %*, …).

Same deliberate difference as before: changing a facility's method keeps the values entered, where the old application reset them.

Tests: `tests/Feature/OthersRevenueTest.php`, `tests/js/others.spec.js`.

---

# Step 08E — Revenue results, reading aids, and the check against the old application

## Revenue results page
`/studies/{id}/revenue`, last item of the study menu ("Revenue results"). Read only (a person who may only view a study can open it). It shows, for every year of the study:
- a stacked bar per year (rooms, food and beverage, gaming, meeting spaces, other revenue) and the exact table with a total per stream and per year (*Total Rooms Revenues*, *Total F&B Revenues*, … *Total Revenues*);
- the figures behind the rooms revenue: *Available Annual Rooms Nights*, *Max. Practical Rooms Nights*, *Rooms Nights Sold*, *Total Guest Count*, *Total Days Count*.
If the rooms rates are not yet entered a clear note says so; if the revenue is zero another note says so.
Server side: `RevenueResultsController` + `App\Services\Revenue\RevenueFiguresService`, which only reads the result of `calculateRoomRevenueAndGuestCount()` (the Step 05 port) — no new calculation was written. Years are keyed by index = calendar year − study start year, as everywhere else.

## Reading aids
The Foods, Gaming, Meetings and Others pages show *Total Guest Count* (and Others also *Total Rooms Revenues* for the "% from rooms revenue" method) under the yearly boxes, in the same table, so each figure sits under its year. Rooms shows *Available Annual Rooms Nights*. They cannot be typed in.

## Checked against the old application
The revenue calculation was run on the old application's code (outside Laravel, on a copy of your MySQL dump) and on the new code, for the same studies, and every number of the result (all 67 keys) was compared:
- 8 studies from your dump, then 13 variants built from one of them (food on cover target / guest capture × value / % of rooms; gaming on guests and on % of rooms; four meeting methods; others on % of rooms; monthly and per-room seasonality; sales channels).
- Result: 21 studies, 0 differences.
Limits, stated plainly: the variants are mostly 1-year studies; the old code was run outside a full Laravel application with a small stand-in for one helper class the dump did not include (taken from our port); the dump's own studies exercise food, gaming and meetings only lightly.

## Not part of this step
Comparing studies, a result link per study on the studies list, and the payback estimate — they need the expense and report steps.

## Tests
`tests/Feature/RevenueResultsTest.php` (hand-checked case: 100 rooms × 2 guests, ADR 1,000 +10% = 1,100, occupancy 60%, 306 days → 30,600 nights available, 18,360 sold, revenue 20,196,000, 36,720 guests) · `tests/js/revenue-results.spec.js`.
