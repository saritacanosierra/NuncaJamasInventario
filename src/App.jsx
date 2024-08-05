
import './App.css'

import { Routes, Route } from 'react-router-dom';
import Home from "./assets/pages/home";

import NotFound from "./assets/pages/notFound";
import Login from './assets/pages/login';
import Register from './assets/pages/register';
import Facture from './assets/pages/facture';
import Inventory from './assets/pages/inventory';


function App() {
  return (
    <div>
      <Routes>
      <Route path="/" element={<Home />} />
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register/>} />
      <Route path="/inventario" element={<Inventory/>} />
      <Route path="/factura" element={<Facture/>} />
       
      <Route path="*" element={<NotFound />} />
      </Routes>
    </div>
  );
}

export default App;
