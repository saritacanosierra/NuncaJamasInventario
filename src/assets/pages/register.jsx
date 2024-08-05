import { useState } from 'react';
import NavbarHorizontal from "../components/navbarHorizontal";
import Input from "../components/input";
import styles from './register.module.css';
import Select from "../components/select";
import Buttons from "../components/buttons";
import loginCss from './login.module.css';
import { Link } from 'react-router-dom';

function Register() {
  const [formData, setFormData] = useState({
    name: '',
    lastName: '',
    password: '',
    password2: '',
    email: '',
    selectName: ''
  });

  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [errors, setErrors] = useState({});

  const options = [
    { value: '...', label: '...' },
    { value: 'Administrador', label: 'Administrador' },
    { value: 'Caja', label: 'Caja' },
    { value: 'Invitado', label: 'Invitado' },
  ];

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    const { name, lastName, password, password2, email, selectName } = formData;

    let formErrors = {};

    if (!name) formErrors.name = true;
    if (!lastName) formErrors.lastName = true;
    if (!password) formErrors.password = true;
    if (!password2) formErrors.password2 = true;
    if (!email) formErrors.email = true;
    if (selectName === '...') formErrors.selectName = true;

    setErrors(formErrors);

    if (Object.keys(formErrors).length > 0) {
      setError('Todos los campos son obligatorios.');
      setSuccess('');
      return;
    }

    if (password !== password2) {
      setError('Las contraseñas no coinciden.');
      setSuccess('');
      return;
    }

    setError('');
    setSuccess('Registro exitoso.');
    setTimeout(() => {
      window.location.href = "/login";
    }, 2000);
  };

  return (
    <>
      <div className={styles.content}>
        <NavbarHorizontal className={styles.navbarRegister} />
        <div className={styles.contentRegister}>
          <h1 className={styles.titleRegister}>Register</h1>

          <form className={styles.form} >
            <div className={styles.col}>
              <div className={styles.contentInput}>
                <label htmlFor="name">Nombre {errors.name && <span className={styles.error}>*</span>}</label>
                <Input
                  className={styles.InputRegister}
                  type="text"
                  placeholder="Nombre..."
                  name="name"
                  id="name"
                  value={formData.name}
                  onChange={handleChange}
                />
              </div>
              <div className={styles.contentInput}>
                <label htmlFor="lastName">Apellido {errors.lastName && <span className={styles.error}>*</span>}</label>
                <Input
                  className={styles.InputRegister}
                  type="text"
                  placeholder="Apellido..."
                  name="lastName"
                  id="lastName"
                  value={formData.lastName}
                  onChange={handleChange}
                />
              </div>
            </div>

            <div className={styles.col}>
              <div className={styles.contentInput}>
                <label htmlFor="password">Contraseña {errors.password && <span className={styles.error}>*</span>}</label>
                <Input
                  className={styles.InputRegister}
                  type="password"
                  placeholder="Contraseña..."
                  name="password"
                  id="password"
                  value={formData.password}
                  onChange={handleChange}
                />
              </div>
              <div className={styles.contentInput}>
                <label htmlFor="password2">Repetir Contraseña {errors.password2 && <span className={styles.error}>*</span>}</label>
                <Input
                  className={styles.InputRegister}
                  type="password"
                  placeholder="Repetir Contraseña..."
                  name="password2"
                  id="password2"
                  value={formData.password2}
                  onChange={handleChange}
                />
              </div>
            </div>

            <div className={styles.col}>
              <div className={styles.contentInput}>
                <label htmlFor="email">Email {errors.email && <span className={styles.error}>*</span>}</label>
                <Input
                  className={styles.InputRegister}
                  type="email"
                  placeholder="Email..."
                  name="email"
                  id="email"
                  value={formData.email}
                  onChange={handleChange}
                />
              </div>
              <div className={`${styles.contentInput} ${styles.selectRegister}`}>

                <label htmlFor="selectName">Tipo Usuario {errors.selectName && <span className={styles.error}>*</span>}</label>
                <Select
                 
                  options={options}
                  name="selectName"
                  id="selectId"
                  value={formData.selectName}
                  onChange={handleChange}
                />
              </div>
            </div>

            {error && <div className={styles.errorMessage}>{error}</div>}
            {success && <div className={styles.successMessage}>{success}</div>}

            <div className={styles.buttonContent}>
              <Buttons className={styles.buttonSubmit} onSubmit={handleSubmit} type="submit" value="registrar">Registrarme</Buttons>
              <Link to="/login" className={loginCss.link}>
                <Buttons className={`${loginCss.buttonButton} ${styles.buttonSubmit}`} type="button">Ingresar</Buttons>
              </Link>
            </div>
          </form>
        </div>
      </div>
    </>
  );
}

export default Register;
