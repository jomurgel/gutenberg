/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Hook to return dimensions of an element.
 *
 * @param {number}   customHeight    new height.
 * @param {number}   customWidth     new width.
 * @param {number}   defaultHeight   original height.
 * @param {number}   defaultWidth    original width.
 * @param {Function} onChange        callback function.
 * @param {boolean}  lockAspectRatio whether or not to retain proportions. default false (legacy).
 *
 * @return {Object} previous height and width and updated height and width.
 */
export default function useDimensionHandler(
	customHeight,
	customWidth,
	defaultHeight,
	defaultWidth,
	onChange,
	lockAspectRatio = false
) {
	// Define the image's aspect ratio.
	const isVertical = defaultHeight > defaultWidth;
	const ratio = isVertical
		? defaultHeight / defaultWidth
		: defaultWidth / defaultHeight;

	const [ currentWidth, setCurrentWidth ] = useState(
		customWidth ?? defaultWidth ?? ''
	);
	const [ currentHeight, setCurrentHeight ] = useState(
		customHeight ?? defaultHeight ?? ''
	);

	// When an image is first inserted, the default dimensions are initially
	// undefined. This effect updates the dimensions when the default values
	// come through.
	useEffect( () => {
		if ( customWidth === undefined && defaultWidth !== undefined ) {
			setCurrentWidth( defaultWidth );
		}
		if ( customHeight === undefined && defaultHeight !== undefined ) {
			setCurrentHeight( defaultHeight );
		}
	}, [ defaultWidth, defaultHeight ] );

	// If custom values change, it means an outsider has resized the image using some other method (eg resize box)
	// this keeps track of these values too. We need to parse before comparing; custom values can be strings.
	useEffect( () => {
		if (
			customWidth !== undefined &&
			Number.parseInt( customWidth ) !== Number.parseInt( currentWidth )
		) {
			setCurrentWidth( customWidth );
		}
		if (
			customHeight !== undefined &&
			Number.parseInt( customHeight ) !== Number.parseInt( currentHeight )
		) {
			setCurrentHeight( customHeight );
		}
	}, [ customWidth, customHeight ] );

	const getValueTest = ( value, multiply = true ) =>
		multiply ? value * ratio : value / ratio;

	/**
	 * Update single value dimension on change.
	 * If lockAspectRatio is true, set height and width simultaneously.
	 *
	 * @param {string}  dimension height or width.
	 * @param {number}  value     new value.
	 * @param {boolean} lock      force-lock proportions.
	 */
	const updateDimension = ( dimension, value, lock = false ) => {
		// If we're supporting aspect ratio locking, update the height and width simultaneously.
		if ( lockAspectRatio || lock ) {
			const nextHeight =
				dimension === 'height'
					? value
					: getValueTest( value, isVertical );
			const nextWidth =
				dimension === 'width'
					? value
					: getValueTest( value, ! isVertical );
			updateDimensions( nextHeight, nextWidth );
		} else if ( dimension === 'width' ) {
			setCurrentWidth( value );
		} else if ( dimension === 'height' ) {
			setCurrentHeight( value );
		}
		onChange( {
			[ dimension ]: value === '' ? undefined : parseInt( value, 10 ),
		} );
	};

	const updateDimensions = ( nextHeight, nextWidth ) => {
		setCurrentHeight( nextHeight ?? defaultHeight );
		setCurrentWidth( nextWidth ?? defaultWidth );
		onChange( { height: nextHeight, width: nextWidth } );
	};

	return {
		currentHeight,
		currentWidth,
		updateDimension,
		updateDimensions,
	};
}
