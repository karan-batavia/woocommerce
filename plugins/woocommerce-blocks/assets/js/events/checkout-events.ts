/**
 * Internal dependencies
 */
import { createEmitter, type EventListener } from './event-emitter';

export const CHECKOUT_EVENTS = {
	CHECKOUT_SUCCESS: 'checkout_success',
	CHECKOUT_FAIL: 'checkout_fail',
	CHECKOUT_VALIDATION: 'checkout_validation',
};

export const checkoutEventsEmitter = createEmitter();
const callbackWrapper =
	( eventName: string ) =>
	( callback: EventListener, priority = 10 ) =>
		checkoutEventsEmitter.subscribe( callback, priority, eventName );
export const checkoutEvents = {
	onCheckoutValidation: callbackWrapper(
		CHECKOUT_EVENTS.CHECKOUT_VALIDATION
	),
	onCheckoutSuccess: callbackWrapper( CHECKOUT_EVENTS.CHECKOUT_SUCCESS ),
	onCheckoutFail: callbackWrapper( CHECKOUT_EVENTS.CHECKOUT_FAIL ),
};
