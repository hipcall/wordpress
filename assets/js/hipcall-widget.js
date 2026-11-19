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

	function clearWidgetSession() {
		removeStoredItem(w.localStorage, sessionKey);
		removeStoredItem(w.sessionStorage, sessionKey);
		removeStoredItem(w.localStorage, prechatKey);
		removeStoredItem(w.sessionStorage, prechatKey);
	}

	function hasVerifiedSessionInStorage(storage) {
		var raw = getStoredItem(storage, sessionKey);
		if (!raw) {
			return false;
		}
		try {
			var parsed = JSON.parse(raw);
			return Boolean(parsed && parsed.userHash);
		} catch (e) {
			return false;
		}
	}

	var prevLocalIdentity = getStoredItem(w.localStorage, identityKey) || '';
	var prevSessionIdentity = getStoredItem(w.sessionStorage, identityKey) || '';
	var previousIdentity = prevLocalIdentity || prevSessionIdentity;

	if (currentIdentity) {
		// Logged-in verified user: if a different verified user was previously active,
		// or if storage had a different userHash, wipe the old widget session completely.
		if (previousIdentity && previousIdentity !== currentIdentity) {
			clearWidgetSession();
		} else {
			var storedRaw = getStoredItem(w.localStorage, sessionKey) || getStoredItem(w.sessionStorage, sessionKey);
			if (storedRaw) {
				try {
					var storedSession = JSON.parse(storedRaw);
					if (storedSession && storedSession.userHash && storedSession.userHash !== currentIdentity) {
						clearWidgetSession();
					}
				} catch (e) {
					clearWidgetSession();
				}
			}
		}

		setStoredItem(w.localStorage, identityKey, currentIdentity);
		setStoredItem(w.sessionStorage, identityKey, currentIdentity);
	} else {
		// Visitor is logged out: if a verified user was previously active in this tab or browser,
		// wipe both localStorage and sessionStorage so the previous user's chat history is not exposed.
		if (
			previousIdentity ||
			hasVerifiedSessionInStorage(w.localStorage) ||
			hasVerifiedSessionInStorage(w.sessionStorage)
		) {
			clearWidgetSession();
			removeStoredItem(w.localStorage, identityKey);
			removeStoredItem(w.sessionStorage, identityKey);
		}
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
