import React from "react";
import ReactDOM from "react-dom/client";
import App from "./App";
import "./styles/index.css";
import "./styles/components.css";

import { NotifyProvider } from "./contexts/Notify.jsx";

ReactDOM.createRoot(document.getElementById("root")).render(
  <React.StrictMode>
    <NotifyProvider>
      <App />
    </NotifyProvider>
  </React.StrictMode>
);
