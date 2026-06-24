// vite.config.js
import { fileURLToPath, URL } from "node:url";
import { defineConfig } from "file:///C:/Users/USER/Workspaces/htdocs/HR%20Onboarding/hrproject%20Vue/hrproject/node_modules/vite/dist/node/index.js";
import vue from "file:///C:/Users/USER/Workspaces/htdocs/HR%20Onboarding/hrproject%20Vue/hrproject/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import vueDevTools from "file:///C:/Users/USER/Workspaces/htdocs/HR%20Onboarding/hrproject%20Vue/hrproject/node_modules/vite-plugin-vue-devtools/dist/vite.mjs";
import Components from "file:///C:/Users/USER/Workspaces/htdocs/HR%20Onboarding/hrproject%20Vue/hrproject/node_modules/unplugin-vue-components/dist/vite.js";
import { PrimeVueResolver } from "file:///C:/Users/USER/Workspaces/htdocs/HR%20Onboarding/hrproject%20Vue/hrproject/node_modules/@primevue/auto-import-resolver/index.mjs";
var __vite_injected_original_import_meta_url = "file:///C:/Users/USER/Workspaces/htdocs/HR%20Onboarding/hrproject%20Vue/hrproject/vite.config.js";
var vite_config_default = defineConfig({
  // base: process.env.NODE_ENV === 'production' ? '/dist/' : '/',
  // publicPath: process.env.NODE_ENV === 'production' ? '/dist/' : '/',
  plugins: [
    vue(),
    Components({
      resolvers: [
        PrimeVueResolver()
      ]
    })
    // vueDevTools(),
  ],
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./src", __vite_injected_original_import_meta_url))
    }
  },
  server: {
    host: "0.0.0.0"
    // Listen on all network interfaces
    // port: 5173,      // Change the port if necessary
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJDOlxcXFxVc2Vyc1xcXFxVU0VSXFxcXFdvcmtzcGFjZXNcXFxcaHRkb2NzXFxcXEhSIE9uYm9hcmRpbmdcXFxcaHJwcm9qZWN0IFZ1ZVxcXFxocnByb2plY3RcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfZmlsZW5hbWUgPSBcIkM6XFxcXFVzZXJzXFxcXFVTRVJcXFxcV29ya3NwYWNlc1xcXFxodGRvY3NcXFxcSFIgT25ib2FyZGluZ1xcXFxocnByb2plY3QgVnVlXFxcXGhycHJvamVjdFxcXFx2aXRlLmNvbmZpZy5qc1wiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9pbXBvcnRfbWV0YV91cmwgPSBcImZpbGU6Ly8vQzovVXNlcnMvVVNFUi9Xb3Jrc3BhY2VzL2h0ZG9jcy9IUiUyME9uYm9hcmRpbmcvaHJwcm9qZWN0JTIwVnVlL2hycHJvamVjdC92aXRlLmNvbmZpZy5qc1wiO2ltcG9ydCB7IGZpbGVVUkxUb1BhdGgsIFVSTCB9IGZyb20gJ25vZGU6dXJsJ1xuXG5pbXBvcnQgeyBkZWZpbmVDb25maWcgfSBmcm9tICd2aXRlJ1xuaW1wb3J0IHZ1ZSBmcm9tICdAdml0ZWpzL3BsdWdpbi12dWUnXG5pbXBvcnQgdnVlRGV2VG9vbHMgZnJvbSAndml0ZS1wbHVnaW4tdnVlLWRldnRvb2xzJ1xuaW1wb3J0IENvbXBvbmVudHMgZnJvbSAndW5wbHVnaW4tdnVlLWNvbXBvbmVudHMvdml0ZSc7XG5pbXBvcnQge1ByaW1lVnVlUmVzb2x2ZXJ9IGZyb20gJ0BwcmltZXZ1ZS9hdXRvLWltcG9ydC1yZXNvbHZlcic7XG5cbi8vIGh0dHBzOi8vdml0ZWpzLmRldi9jb25maWcvXG5leHBvcnQgZGVmYXVsdCBkZWZpbmVDb25maWcoe1xuICAvLyBiYXNlOiBwcm9jZXNzLmVudi5OT0RFX0VOViA9PT0gJ3Byb2R1Y3Rpb24nID8gJy9kaXN0LycgOiAnLycsXG4gIC8vIHB1YmxpY1BhdGg6IHByb2Nlc3MuZW52Lk5PREVfRU5WID09PSAncHJvZHVjdGlvbicgPyAnL2Rpc3QvJyA6ICcvJyxcblxuICBwbHVnaW5zOiBbXG4gICAgdnVlKCksXG4gICAgQ29tcG9uZW50cyh7XG4gICAgICByZXNvbHZlcnM6IFtcbiAgICAgICAgUHJpbWVWdWVSZXNvbHZlcigpXG4gICAgICBdXG4gICAgfSksXG4gICAgLy8gdnVlRGV2VG9vbHMoKSxcbiAgXSxcbiAgcmVzb2x2ZToge1xuICAgIGFsaWFzOiB7XG4gICAgICAnQCc6IGZpbGVVUkxUb1BhdGgobmV3IFVSTCgnLi9zcmMnLCBpbXBvcnQubWV0YS51cmwpKVxuICAgIH1cbiAgfSxcbiAgc2VydmVyOiB7XG4gICAgaG9zdDogJzAuMC4wLjAnLCAvLyBMaXN0ZW4gb24gYWxsIG5ldHdvcmsgaW50ZXJmYWNlc1xuICAgIC8vIHBvcnQ6IDUxNzMsICAgICAgLy8gQ2hhbmdlIHRoZSBwb3J0IGlmIG5lY2Vzc2FyeVxuICB9XG59KVxuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUFxWixTQUFTLGVBQWUsV0FBVztBQUV4YixTQUFTLG9CQUFvQjtBQUM3QixPQUFPLFNBQVM7QUFDaEIsT0FBTyxpQkFBaUI7QUFDeEIsT0FBTyxnQkFBZ0I7QUFDdkIsU0FBUSx3QkFBdUI7QUFOa08sSUFBTSwyQ0FBMkM7QUFTbFQsSUFBTyxzQkFBUSxhQUFhO0FBQUE7QUFBQTtBQUFBLEVBSTFCLFNBQVM7QUFBQSxJQUNQLElBQUk7QUFBQSxJQUNKLFdBQVc7QUFBQSxNQUNULFdBQVc7QUFBQSxRQUNULGlCQUFpQjtBQUFBLE1BQ25CO0FBQUEsSUFDRixDQUFDO0FBQUE7QUFBQSxFQUVIO0FBQUEsRUFDQSxTQUFTO0FBQUEsSUFDUCxPQUFPO0FBQUEsTUFDTCxLQUFLLGNBQWMsSUFBSSxJQUFJLFNBQVMsd0NBQWUsQ0FBQztBQUFBLElBQ3REO0FBQUEsRUFDRjtBQUFBLEVBQ0EsUUFBUTtBQUFBLElBQ04sTUFBTTtBQUFBO0FBQUE7QUFBQSxFQUVSO0FBQ0YsQ0FBQzsiLAogICJuYW1lcyI6IFtdCn0K
