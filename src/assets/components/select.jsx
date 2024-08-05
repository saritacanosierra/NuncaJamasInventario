
import PropTypes from 'prop-types';
import css from './input.module.css';
import selectCss from './select.module.css';

const Select = ({ options, name, id }) => {
    return (
        <div>
           <select className={`${css.input} ${selectCss.inputSelect}`} name={name} id={id}>

                {options.map((option, index) => (
                    <option key={index} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </div>
    );
};

Select.propTypes = {
    options: PropTypes.arrayOf(
        PropTypes.shape({
            value: PropTypes.string.isRequired,
            label: PropTypes.string.isRequired,
        })
    ).isRequired,
    name: PropTypes.string.isRequired,
    id: PropTypes.string.isRequired,
};

export default Select;
