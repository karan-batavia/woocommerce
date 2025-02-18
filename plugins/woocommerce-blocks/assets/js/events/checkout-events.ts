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

export const checkoutEvents = {
	onCheckoutValidation: checkoutEventsEmitter.createSubscribeFunction(
		CHECKOUT_EVENTS.CHECKOUT_VALIDATION
	),
	onCheckoutSuccess: checkoutEventsEmitter.createSubscribeFunction(
		CHECKOUT_EVENTS.CHECKOUT_SUCCESS
	),
	onCheckoutFail: checkoutEventsEmitter.createSubscribeFunction(
		CHECKOUT_EVENTS.CHECKOUT_FAIL
	),
};
