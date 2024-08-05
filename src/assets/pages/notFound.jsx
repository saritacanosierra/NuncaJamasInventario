import Buttons from "../components/buttons"
import notFound from "./notFound.module.css"
import { Link } from 'react-router-dom';
function NotFound() {


  return (
  <>
  <div className={notFound.content}>
  <h1>pagina no encontrada</h1>
  <p>regresa a la pagina de inicio</p>
  <Buttons className={notFound.btnInicio}><Link to="/">
      Inicio
  </Link>
  </Buttons>
  
  </div>
</>
)
}
export default NotFound;
