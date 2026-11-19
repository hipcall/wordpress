/**
 * Hipcall WordPress Plugin - Frontend Widget Initialization & Session Isolation.
 *
 * Expects `window.hipcallWp` to be defined before this script runs:
 * {
 *   widgetId: "uuid",
 *   user: { email: "...", name: "...", user_hash: "..." } | null
 * }
 */
(function (w) {
	'use strict';

	var config = w.hipcallWp;
	if (!config || !config.widgetId) {
		return;
	}

	var widgetId = String(config.widgetId);
	var sessionKey = 'hipcall_session_' + widgetId;
	var prechatKey = 'hipcall_prechat_submitted_' + widgetId;
	var identityKey = 'hipcall_wp_identity_' + widgetId;
	var currentUser = config.user && config.user.user_hash ? config.user : null;
	var currentIdentity = currentUser ? String(currentUser.user_hash) : '';

	function getStoredItem(storage, key) {
		try {
			return storage ? storage.getItem(key) : null;
		} catch (e) {
			return null;
		}
	}

	function setStoredItem(storage, key, value) {
		try {
			if (storage) {
				storage.setItem(key, value);
			}
		} catch (e) {
			// Ignore storage quota or privacy mode errors.
		}
	}

	function removeStoredItem(storage, key) {
		try {
			if (storage) {
				storage.removeItem(key);
			}
		} catch (e) {
			// Ignore storage access errors.
		}
	}

	function removeCookie(name) {
		try {
			var expired = '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax';
			w.document.cookie = encodeURIComponent(name) + expired;
			w.document.cookie = encodeURIComponent(name) + expired + '; domain=' + w.location.hostname;
		} catch (e) {
			// Ignore cookie access errors.
		}
	}

	// Remove every copy of the widget session (localStorage, sessionStorage and the fallback cookie)
	// so the widget starts a fresh session on its own. The widget's internal fields are never read or written.
	function clearWidgetSession() {
		removeStoredItem(w.localStorage, sessionKey);
		removeStoredItem(w.sessionStorage, sessionKey);
		removeStoredItem(w.localStorage, prechatKey);
		removeStoredItem(w.sessionStorage, prechatKey);
		removeCookie(sessionKey);
		removeCookie(prechatKey);
	}

	// "Did the user change?" is answered only from our own key, which stores the last user_hash.
	var previousIdentity =
		getStoredItem(w.localStorage, identityKey) || getStoredItem(w.sessionStorage, identityKey) || '';

	if (currentIdentity) {
		// Logged-in verified user: if a different verified user was previously active, wipe the old session.
		if (previousIdentity && previousIdentity !== currentIdentity) {
			clearWidgetSession();
		}

		setStoredItem(w.localStorage, identityKey, currentIdentity);
		setStoredItem(w.sessionStorage, identityKey, currentIdentity);
	} else if (previousIdentity) {
		// Visitor is logged out but a verified user was previously active in this browser or tab:
		// wipe the session so the previous user's chat history is not exposed.
		clearWidgetSession();
		removeStoredItem(w.localStorage, identityKey);
		removeStoredItem(w.sessionStorage, identityKey);
	}

	// Initialize the Hipcall command queue before the async widget script loads.
	w.HipcallWidget = 'hc';
	w.hc =
		w.hc ||
		function () {
			(w.hc.q = w.hc.q || []).push(arguments);
		};

	if (currentUser) {
		w.hc('user', {
			email: currentUser.email,
			name: currentUser.name,
			user_hash: currentUser.user_hash
		});
	}
})(window);
