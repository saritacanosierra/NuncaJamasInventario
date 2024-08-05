import { useState } from "react";
import drawerCss from "./drawerCss.module.css";
import { Link } from "react-router-dom";

function Drawer() {
    const [isOpen, setIsOpen] = useState(false);

    const toggleDrawer = () => {
        setIsOpen(!isOpen);
    };

    return (
        <>
            <div className={drawerCss.content}>
                <div className={isOpen ? `${drawerCss.drawer} ${drawerCss.open}` : drawerCss.drawer
                }>
                    <section className={drawerCss.sidebar}>



                        <div className={drawerCss.sidebarContent} onClick={toggleDrawer}>
                            <ul className={drawerCss.sidebarList} onClick={toggleDrawer}>
                                <li>
                                    <Link to="/home">

                                        <span className={`${drawerCss.icons} material-symbols-outlined`}> <img className={drawerCss.imgLogo} src="../../../public/img/logo.png" alt="" /></span>
                                    </Link>
                                </li>


                                <li className={drawerCss.sidebarListItem}>
                                    <Link to="/Home"><p>Home</p>
                                        <span className={`${drawerCss.icons} material-symbols-outlined`}>menu</span>
                                    </Link>
                                </li>

                                <li className={drawerCss.sidebarListItem}>
                                    <Link>
                                        <p>Home</p>
                                        <span className={`${drawerCss.icons} material-symbols-outlined`}>person</span>
                                    </Link>
                                </li>
                                <li className={drawerCss.sidebarListItem}>
                                    <Link to="/inventario"> <p>Inventario</p>
                                        <span className={`${drawerCss.icons} material-symbols-outlined`}>inventory</span>
                                    </Link>
                                </li>
                                <li className={drawerCss.sidebarListItem}>
                                    <Link to="/factura"><p>factura</p>
                                        <span className={`${drawerCss.icons} material-symbols-outlined`}>
                                            payments
                                        </span>
                                    </Link>
                                </li>
                                <li className={drawerCss.sidebarListItem}>
                                    <Link to="/Historial"><p>Historial</p>
                                        <span className={`${drawerCss.icons} material-symbols-outlined`}>query_stats</span>
                                    </Link>
                                </li>
                                <li className={drawerCss.sidebarListItem}>
                                    <Link to="/clientes"><p>Clientes</p>
                                        <span className={`${drawerCss.icons} material-symbols-outlined`}>folder_shared</span>
                                    </Link>
                                </li>
                                <li className={drawerCss.sidebarListItem}>
                                    <Link to="/configuracion"><p>Configuracion</p>
                                        <span className={`${drawerCss.icons} material-symbols-outlined`}>
                                            manufacturing
                                        </span>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </section>
                  
                </div >
            </div >
        </>
    );
}

export default Drawer;
