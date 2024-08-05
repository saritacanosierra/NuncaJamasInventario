import Drawer from "../components/drawer";
import factureCss from "./inventory.module.css"




function Facture() {
  return (
  <>

  <div className={factureCss.contentAll}>
    <nav>
  <Drawer/>
  </nav>
  <div className={factureCss.content}>
  <h1>Facture</h1>
  </div>
  
  

</div>
  </>




  )
}

export default Facture;
