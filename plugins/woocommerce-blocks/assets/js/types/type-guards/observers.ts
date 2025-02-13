/**
 * Internal dependencies
 */
import { FieldValidationStatus, isObject, objectHasProp } from '../';

type ResponseTypes = 'success' | 'failure' | 'error';

export interface ObserverResponse {
	type: ResponseTypes;
	errorMessage?: string;
	context?: string;
	meta?: Record< string, unknown >;
	validationErrors?: Record< string, FieldValidationStatus >;
}

/**
 * Whether the passed object is an ObserverResponse.
 */
export const isObserverResponse = (
	response: unknown
): response is ObserverResponse => {
	return isObject( response ) && objectHasProp( response, 'type' );
};

export interface ResponseType extends Record< string, unknown > {
	type: ResponseTypes;
	retry?: boolean;
}

const isResponseOf = (
	response: unknown,
	type: string extends ResponseTypes ? never : ResponseTypes
): response is ResponseType => {
	return isObject( response ) && 'type' in response && response.type === type;
};

export const isSuccessResponse = (
	response: unknown
): response is ObserverFailResponse => {
	return isResponseOf( response, 'success' );
};
interface ObserverSuccessResponse extends ObserverResponse {
	type: 'success';
}
export const isErrorResponse = (
	response: unknown
): response is ObserverSuccessResponse => {
	return isResponseOf( response, 'error' );
};
interface ObserverErrorResponse extends ObserverResponse {
	type: 'error';
}

interface ObserverFailResponse extends ObserverResponse {
	type: 'failure';
}
export const isFailResponse = (
	response: unknown
): response is ObserverErrorResponse => {
	return isResponseOf( response, 'failure' );
};
