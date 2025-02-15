/**
 * External dependencies
 */
import classnames from 'classnames';
import type { ForwardedRef } from 'react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { forwardRef } from '@wordpress/element';
import { external } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { VisuallyHidden } from '../visually-hidden';
import { StyledIcon } from './styles/external-link-styles';
import type { ExternalLinkProps } from './types';
import type { WordPressComponentProps } from '../ui/context';

function UnforwardedExternalLink(
	props: Omit<
		WordPressComponentProps< ExternalLinkProps, 'a', false >,
		'target'
	>,
	ref: ForwardedRef< HTMLAnchorElement >
) {
	const { href, children, className, rel = '', ...additionalProps } = props;
	const optimizedRel = [
		...new Set(
			[
				...rel.split( ' ' ),
				'external',
				'noreferrer',
				'noopener',
			].filter( Boolean )
		),
	].join( ' ' );
	const classes = classnames( 'components-external-link', className );
	/* Anchor links are percieved as external links.
	This constant helps check for on page anchor links,
	to prevent them from being opened in the editor. */
	const isInternalAnchor = !! href?.startsWith( '#' );

	return (
		/* eslint-disable react/jsx-no-target-blank */
		<a
			{ ...additionalProps }
			className={ classes }
			href={ href }
			onClick={
				isInternalAnchor
					? ( event ) => event.preventDefault()
					: undefined
			}
			target="_blank"
			rel={ optimizedRel }
			ref={ ref }
		>
			{ children }
			<VisuallyHidden as="span">
				{
					/* translators: accessibility text */
					__( '(opens in a new tab)' )
				}
			</VisuallyHidden>
			<StyledIcon
				icon={ external }
				className="components-external-link__icon"
			/>
		</a>
		/* eslint-enable react/jsx-no-target-blank */
	);
}

/**
 * Link to an external resource.
 *
 * ```jsx
 * import { ExternalLink } from '@wordpress/components';
 *
 * const MyExternalLink = () => (
 *   <ExternalLink href="https://wordpress.org">WordPress.org</ExternalLink>
 * );
 * ```
 */
export const ExternalLink = forwardRef( UnforwardedExternalLink );

export default ExternalLink;
