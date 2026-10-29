import { describe, it, expect } from 'vitest';
import en from '../../resources/js/lang/en.js';

// The field names of the old application, copied from its pages. If someone
// "improves" a name, this test says so.
const get = (path) => path.split('.').reduce((o, k) => o?.[k], en);

describe('field names follow the reference project', () => {
    const names = {
        'studies.streams.foods.title': 'Food & Beverage (F&B) Revenue Stream',
        'studies.streams.foods.count': 'F&B Facility Count',
        'studies.streams.foods.value': 'Guest Capacity',
        'studies.streams.casinos.title': 'Gaming Revenue Stream',
        'studies.streams.casinos.type': 'Gaming Facility Types',
        'studies.streams.casinos.count': 'Gaming Facility Count',
        'studies.streams.casinos.value': 'Guest Capacity',
        'studies.streams.meetings.count': 'Meeting Facility Count',
        'studies.streams.meetings.value': 'Guest Capacity',
        'studies.streams.others.count': 'Other Facility Count',
        'revenue.foods.facilities.seatsEach': 'Guest Capacity',
        'revenue.foods.facilities.count': 'F&B Facility Count',
        'revenue.foods.cover.escalation': 'Cover Value Escalation Rate %',
        'revenue.casinos.cover.annual': 'Charges Per Guest Annual Escalation Rate %',
        'revenue.casinos.yearly.target': 'Target Guest Count Per Day',
        'revenue.meetings.facilities.count': 'Meeting Spaces Facility Count',
        'revenue.meetings.cover.dailyRent': 'Daily Rent Per Facility',
        'revenue.meetings.yearly.occupancy': 'Occupancy Rate %',
        'revenue.rooms.adr': 'Average Daily Rate (ADR)',
    };
    for (const [path, value] of Object.entries(names)) {
        it(path, () => expect(get(path)).toBe(value));
    }
    it('uses none of the invented words on the study form', () => {
        const text = JSON.stringify(en.studies.streams);
        for (const bad of ['Seats', 'Seat turns', 'Tables', 'Players', 'Units', 'Casino', 'Gaming area', 'Events per day']) {
            expect(text).not.toContain(bad);
        }
    });
});
