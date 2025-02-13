/**
 * External dependencies
 */
import {
	isErrorResponse,
	isFailResponse,
	isObserverResponse,
	type ObserverResponse,
} from '@woocommerce/types';

export interface EventEmitter {
	emit: (
		eventName: string,
		data?: unknown
	) => Promise< ObserverResponse[] >;
	subscribe: ( listener: EventListener, eventName: string ) => VoidFunction;
}

export type EventListener = (
	data: unknown
) => Promise< ObserverResponse | true > | ObserverResponse | true;

/**
 * Create an event emitter.
 *
 * @return The event emitter.
 */
export function createEmitter(): EventEmitter {
	const listeners = new Map< string, Set< EventListener > >();
	const notifyListeners = async ( eventName: string, data: unknown ) => {
		const listenersForEvent =
			listeners.get( eventName ) || new Set< EventListener >();
		// We use Array.from to clone the listeners Set. This ensures that we don't run a listener that was added as a
		// response to another listener.
		const clonedListeners = Array.from( listenersForEvent );
		const responses = [];
		for ( const listener of clonedListeners ) {
			const observerResponse = await listener( data );
			if ( isObserverResponse( observerResponse ) ) {
				responses.push( observerResponse );
			}
		}
		return responses;
	};

	const notifyListenersWithAbort = async (
		eventName: string,
		data: unknown
	) => {
		const listenersForEvent =
			listeners.get( eventName ) || new Set< EventListener >();
		const clonedListeners = Array.from( listenersForEvent );
		const responses = [];
		try {
			for ( const listener of clonedListeners ) {
				const observerResponse = await listener( data );
				if ( isObserverResponse( observerResponse ) ) {
					responses.push( observerResponse );
				}
				if (
					isErrorResponse( observerResponse ) ||
					isFailResponse( observerResponse )
				) {
					return responses;
				}
			}
		} catch ( e ) {
			// We don't care about errors blocking execution,
			// but will console.error for troubleshooting.
			// eslint-disable-next-line no-console
			console.error( e );
			responses.push( { type: 'error' } );
			return responses;
		}
		return responses;
	};

	return {
		subscribe( listener, eventName: string ) {
			let listenersForEvent =
				listeners.get( eventName ) || new Set< EventListener >();
			listenersForEvent.add( listener );
			listeners.set( eventName, listenersForEvent );
			return () => {
				// Re-get the listeners for the event in case the list was updated before unsubscribe was called.
				listenersForEvent =
					listeners.get( eventName ) || new Set< EventListener >();
				listenersForEvent.delete( listener );
				listeners.set( eventName, listenersForEvent );
			};
		},

		emit: async ( eventName: string, data: unknown ) => {
			return await notifyListeners( eventName, data );
		},

		emitWithAbort: async ( eventName: string, data: unknown ) => {
			const results = await notifyListenersWithAbort( eventName, data );
		},
	};
}
