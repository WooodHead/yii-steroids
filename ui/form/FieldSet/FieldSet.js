import React from 'react';
import PropTypes from 'prop-types';

export default class FieldSet extends React.PureComponent {

    static propTypes = {
        model: PropTypes.oneOfType([
            PropTypes.string,
            PropTypes.func,
        ]),
        prefix: PropTypes.string,
        layout: PropTypes.string,
        layoutProps: PropTypes.object,
        size: PropTypes.oneOf(['sm', 'md', 'lg']),
    };

    static contextTypes = {
        model: PropTypes.oneOfType([
            PropTypes.string,
            PropTypes.func,
        ]),
        prefix: PropTypes.string,
        layout: PropTypes.string,
        layoutProps: PropTypes.object,
        size: PropTypes.oneOf(['sm', 'md', 'lg']),
    };

    static childContextTypes = {
        model: PropTypes.oneOfType([
            PropTypes.string,
            PropTypes.func,
        ]),
        prefix: PropTypes.string,
        layout: PropTypes.string,
        layoutProps: PropTypes.object,
        size: PropTypes.oneOf(['sm', 'md', 'lg']),
    };

    getChildContext() {
        return {
            model: this.props.model || this.context.model,
            prefix: (this.context.prefix || '') + (this.props.prefix || ''),
            layout: this.props.layout || this.context.layout,
            layoutProps: {
                ...this.context.layoutProps,
                ...this.props.layoutProps,
            },
            size: this.props.size || this.context.size,
        };
    }

    render() {
        return this.props.children;
    }

}
