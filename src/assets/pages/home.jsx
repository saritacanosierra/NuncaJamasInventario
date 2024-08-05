import Drawer from "../components/drawer";
import homeCss from "./home.module.css"




function Home() {
  return (
  <>

  <div className={homeCss.contentAll}>
    <nav>
  <Drawer/>
  </nav>
  <div className={homeCss.content}>
  <h1>home</h1>
  </div>
  
  

</div>
  </>




  )
}

export default Home;
