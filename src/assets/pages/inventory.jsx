import Drawer from "../components/drawer";
import inventoryCss from "./inventory.module.css"
import Buttons from '../components/buttons'
import Input from "../components/input";



function Inventory() {




  return (
    <>

      <div className={inventoryCss.contentAll}>
        <nav>
          <Drawer />
        </nav>
        <div className={inventoryCss.content}>
          <h1>Inventory</h1>
          <div className={inventoryCss.filter}>
            <Buttons className={inventoryCss.addProductButton}>Nuevo Producto +</Buttons>
            <Input
              type="text"
              placeholder="Search"
              className={inventoryCss.searchInput}
            />
            <Buttons className={inventoryCss.searchButton}>
              <span className="material-symbols-outlined">
                search
              </span></Buttons>



          </div>

        </div>



      </div>
    </>




  )
}

export default Inventory;
