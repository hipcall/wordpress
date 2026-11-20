/**
 * Tests for assets/js/hipcall-widget.js (session isolation and widget command queue).
 *
 * The script is an IIFE that reads window.hipcallWp, so each test sets up storage and config,
 * then runs the script source in the jsdom window.
 */
const fs = require('fs');
const path = require('path');

const SOURCE = fs.readFileSync(path.join(__dirname, '../../assets/js/hipcall-widget.js'), 'utf8');

const WIDGET_ID = '8e32e0b8-fd33-450b-a5b0-b3359e1384e1';
const SESSION_KEY = 'hipcall_session_' + WIDGET_ID;
const PRECHAT_KEY = 'hipcall_prechat_submitted_' + WIDGET_ID;
const IDENTITY_KEY = 'hipcall_wp_identity_' + WIDGET_ID;

const USER_A = { email: 'a@example.com', name: 'User A', user_hash: 'a'.repeat(64) };
const USER_B = { email: 'b@example.com', name: 'User B', user_hash: 'b'.repeat(64) };

const SESSION_VALUE = JSON.stringify({ sessionToken: 'token-of-previous-session' });

function runScript(config) {
	window.hipcallWp = config;
	// eslint-disable-next-line no-new-func
	new Function(SOURCE)();
}

/** Simulate a previous page view where `user` was logged in and had an active chat. */
function seedActiveChat(user) {
	for (const storage of [window.localStorage, window.sessionStorage]) {
		storage.setItem(SESSION_KEY, SESSION_VALUE);
		storage.setItem(PRECHAT_KEY, '1');
		if (user) {
			storage.setItem(IDENTITY_KEY, user.user_hash);
		}
	}
	document.cookie = SESSION_KEY + '=cookie-copy; path=/';
}

function expectSessionCleared() {
	for (const storage of [window.localStorage, window.sessionStorage]) {
		expect(storage.getItem(SESSION_KEY)).toBeNull();
		expect(storage.getItem(PRECHAT_KEY)).toBeNull();
	}
	expect(document.cookie).not.toContain(SESSION_KEY + '=');
}

function expectSessionKept() {
	for (const storage of [window.localStorage, window.sessionStorage]) {
		expect(storage.getItem(SESSION_KEY)).toBe(SESSION_VALUE);
	}
	expect(document.cookie).toContain(SESSION_KEY + '=cookie-copy');
}

function queuedCommands() {
	return window.hc && window.hc.q ? window.hc.q.map((args) => Array.from(args)) : [];
}

beforeEach(() => {
	window.localStorage.clear();
	window.sessionStorage.clear();
	document.cookie.split(';').forEach((c) => {
		const name = c.split('=')[0].trim();
		if (name) {
			document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
		}
	});
	delete window.hipcallWp;
	delete window.hc;
	delete window.HipcallWidget;
});

describe('without configuration', () => {
	test('does nothing when window.hipcallWp is missing', () => {
		runScript(undefined);
		expect(window.hc).toBeUndefined();
	});

	test('does nothing when widgetId is empty', () => {
		seedActiveChat(USER_A);
		runScript({ widgetId: '', user: null });
		expect(window.hc).toBeUndefined();
		expectSessionKept();
	});
});

describe('command queue', () => {
	test('creates the hc queue and identifies a verified user', () => {
		runScript({ widgetId: WIDGET_ID, user: USER_A });

		expect(window.HipcallWidget).toBe('hc');
		expect(queuedCommands()).toEqual([['user', USER_A]]);
	});

	test('does not send a user command for anonymous visitors', () => {
		runScript({ widgetId: WIDGET_ID, user: null });

		expect(typeof window.hc).toBe('function');
		expect(queuedCommands()).toEqual([]);
	});

	test('ignores a user object without user_hash', () => {
		runScript({ widgetId: WIDGET_ID, user: { email: 'a@example.com', name: 'A' } });

		expect(queuedCommands()).toEqual([]);
		expect(window.localStorage.getItem(IDENTITY_KEY)).toBeNull();
	});

	test('keeps commands queued before the script ran', () => {
		window.hc = function () {
			(window.hc.q = window.hc.q || []).push(arguments);
		};
		window.hc('open');

		runScript({ widgetId: WIDGET_ID, user: USER_A });

		expect(queuedCommands()).toEqual([['open'], ['user', USER_A]]);
	});
});

describe('session isolation (review item 1.1)', () => {
	test('first visit of a logged-in user stores only the user_hash as identity', () => {
		runScript({ widgetId: WIDGET_ID, user: USER_A });

		expect(window.localStorage.getItem(IDENTITY_KEY)).toBe(USER_A.user_hash);
		expect(window.sessionStorage.getItem(IDENTITY_KEY)).toBe(USER_A.user_hash);
		expect(JSON.stringify({ ...window.localStorage })).not.toContain(USER_A.email);
	});

	test('same user on the next page keeps the chat session', () => {
		seedActiveChat(USER_A);

		runScript({ widgetId: WIDGET_ID, user: USER_A });

		expectSessionKept();
	});

	test('user A logs out: the whole session is wiped (same tab and new tab)', () => {
		seedActiveChat(USER_A);

		runScript({ widgetId: WIDGET_ID, user: null });

		expectSessionCleared();
		expect(window.localStorage.getItem(IDENTITY_KEY)).toBeNull();
		expect(window.sessionStorage.getItem(IDENTITY_KEY)).toBeNull();
	});

	test('user A logs out in a new tab: identity only in localStorage still wipes sessionStorage copy', () => {
		seedActiveChat(USER_A);
		window.sessionStorage.removeItem(IDENTITY_KEY);

		runScript({ widgetId: WIDGET_ID, user: null });

		expectSessionCleared();
	});

	test('user A logs out in the same tab: identity only in sessionStorage still wipes localStorage copy', () => {
		seedActiveChat(USER_A);
		window.localStorage.removeItem(IDENTITY_KEY);

		runScript({ widgetId: WIDGET_ID, user: null });

		expectSessionCleared();
	});

	test('user B logs in after user A: A\'s session is wiped and B becomes the identity', () => {
		seedActiveChat(USER_A);

		runScript({ widgetId: WIDGET_ID, user: USER_B });

		expectSessionCleared();
		expect(window.localStorage.getItem(IDENTITY_KEY)).toBe(USER_B.user_hash);
		expect(window.sessionStorage.getItem(IDENTITY_KEY)).toBe(USER_B.user_hash);
		expect(queuedCommands()).toEqual([['user', USER_B]]);
	});

	test('anonymous visitor keeps their own chat between pages', () => {
		seedActiveChat(null);

		runScript({ widgetId: WIDGET_ID, user: null });

		expectSessionKept();
	});

	test('never writes the widget\'s own session data', () => {
		runScript({ widgetId: WIDGET_ID, user: USER_A });

		expect(window.localStorage.getItem(SESSION_KEY)).toBeNull();
		expect(window.sessionStorage.getItem(SESSION_KEY)).toBeNull();
	});

	test('sessions of other widgets are not touched', () => {
		const otherKey = 'hipcall_session_e61f8fe3-3eea-44d0-bd1f-416c143c710c';
		seedActiveChat(USER_A);
		window.localStorage.setItem(otherKey, SESSION_VALUE);

		runScript({ widgetId: WIDGET_ID, user: null });

		expect(window.localStorage.getItem(otherKey)).toBe(SESSION_VALUE);
	});
});

describe('restricted browsers', () => {
	test('still initializes the widget queue when storage access throws (e.g. blocked cookies)', () => {
		const throwing = () => {
			throw new DOMException('The operation is insecure.', 'SecurityError');
		};
		const local = Object.getOwnPropertyDescriptor(window, 'localStorage');
		const session = Object.getOwnPropertyDescriptor(window, 'sessionStorage');
		Object.defineProperty(window, 'localStorage', { configurable: true, get: throwing });
		Object.defineProperty(window, 'sessionStorage', { configurable: true, get: throwing });

		try {
			expect(() => runScript({ widgetId: WIDGET_ID, user: USER_A })).not.toThrow();
			expect(queuedCommands()).toEqual([['user', USER_A]]);
		} finally {
			Object.defineProperty(window, 'localStorage', local);
			Object.defineProperty(window, 'sessionStorage', session);
		}
	});
});
