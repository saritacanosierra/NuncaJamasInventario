
import { useState } from 'react';
import NavbarHorizontal from '../components/navbarHorizontal';
import Input from '../components/input'
import Buttons from '../components/buttons'
import loginCss from './login.module.css';
import { Link } from 'react-router-dom';




function Login() {

    const [passwordVisible, setPasswordVisible] = useState(true);

    const togglePasswordVisibility = () => {
        setPasswordVisible(!passwordVisible);
    };

    const users = [
        { username: "Sara", password: "123" }
    ];

    const handleSubmit = (event) => {
        event.preventDefault();
        const data = new FormData(event.target);
        const enteredUsername = data.get('username');
        const enteredPassword = data.get('password');

        if (!enteredUsername || !enteredPassword) {
            alert('Por favor, complete todos los campos');
            return;
        }

        const user = users.find(user =>
            user.username.toLowerCase() === enteredUsername.toLowerCase() &&
            user.password === enteredPassword
        );

        if (user) {
            alert('Bienvenido');

            window.location.replace('/')
        } else {

            alert('Usuario y/o contraseña incorrectos');

        }
    }


    return (
        <>
            <NavbarHorizontal />
            <div className={loginCss.Login} >
                <section className={loginCss.secationLeft}>
                    <h1 className={loginCss.titleLogin}>login</h1>
                    <form className={loginCss.form} onSubmit={handleSubmit}>
                        <div className={loginCss.contentInputs}>
                            <div className={loginCss.input1}>
                                <label htmlFor="username">Ingrese su Usuario</label>
                                <div className={loginCss.icon}>
                                    <div className={loginCss.inputContent}>
                                        <Input className={loginCss.inputLogin} type="text" placeholder="username" name="username" id="username" />
                                    </div>
                                    <i><span className="material-symbols-outlined">person</span></i>
                                </div>
                            </div>
                            <div className={loginCss.input2}>
                                <label htmlFor="password">Ingrese su Contraseña</label>
                                <div className={loginCss.icon}>
                                    <Input className={loginCss.inputLogin} 
                                    type={passwordVisible ? 'text' : 'password'} 
                                    placeholder="password" 
                                    name="password" 
                                    id="password" />
                                    <i><span className="material-symbols-outlined">key</span> </i>
                                    <button
                                        type='button'
                                        onClick={togglePasswordVisibility}
                                        className={loginCss.eyeButton}
                                    >
                                        <span className="material-symbols-outlined">
                                            {passwordVisible ? 'visibility_off' : 'visibility'}
                                        </span>
                                    </button>

                                </div>
                            </div>
                        </div>
                        <div>
                            <div className={loginCss.buttonContent}>
                                <Buttons className={loginCss.buttonSubmit} type="submit" id="loginUser" name="loginUser">Ingresar</Buttons>
                                <Link to="/register" className={loginCss.link}>
                                    <Buttons className={loginCss.buttonButton} type="button">Registrarme</Buttons>
                                </Link>
                            </div>
                        </div>
                    </form>
                </section>
                <section className={loginCss.secationRigth}>
                    <img className={loginCss.imgLogin} src="../../../public/img/tutu login.JPG" alt="" />
                </section>
            </div >
        </>
    );
}

export default Login;
