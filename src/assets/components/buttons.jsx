import PropTypes from 'prop-types';
import css from './buttons.module.css';

const Buttons = ({ type, name, id, className, children }) => {
    return (
        <div className={css.buttonContent}>
        <button
          type={type}
          name={name}
          id={id}
          className={`${css.buttons} ${className}`}>
          {children}
        </button>
        </div>
    );
};

Buttons.propTypes = {
    type: PropTypes.string.isRequired,
    name: PropTypes.string.isRequired,
    id: PropTypes.string.isRequired,
    className: PropTypes.string,
    children: PropTypes.node.isRequired,
};

export default Buttons;
