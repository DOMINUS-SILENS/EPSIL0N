import { defineConfig } from "vite";
import { semanticTopologyPlugin } from "./tooling/semanticTopologyPlugin.mjs";

export default defineConfig({
  plugins: [semanticTopologyPlugin()],
});
