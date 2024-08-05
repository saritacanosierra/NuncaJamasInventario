import PropTypes from 'prop-types';
import css from './input.module.css';

const Input = ({ type, placeholder, name, id, className }) => {
  return (
    <div className={css.styledInput}>
      <input
        type={type}
        placeholder={placeholder}
        name={name}
        id={id}
        className={`${css.input} ${className}`} />
    </div>
  );
};

Input.propTypes = {
  type: PropTypes.string.isRequired,
  placeholder: PropTypes.string,
  name: PropTypes.string.isRequired,
  id: PropTypes.string.isRequired,
  className: PropTypes.string,
};

export default Input;
