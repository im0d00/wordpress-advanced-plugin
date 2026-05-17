import express from "express";
import path from "path";
import fs from "fs";
import archiver from "archiver";
import { createServer as createViteServer } from "vite";

async function startServer() {
  const app = express();
  const PORT = 3000;

  // API Route to download the WordPress plugin
  app.get("/api/download-plugin", (req, res) => {
    const pluginDir = path.join(process.cwd(), "nexusbuilder");
    
    if (!fs.existsSync(pluginDir)) {
      // Create empty dir just in case
      fs.mkdirSync(pluginDir, { recursive: true });
    }

    res.attachment("nexusbuilder.zip");
    
    const archive = archiver("zip", {
      zlib: { level: 9 } // Sets the compression level.
    });

    archive.on("warning", function (err) {
      if (err.code === "ENOENT") {
        console.warn(err);
      } else {
        throw err;
      }
    });

    archive.on("error", function (err) {
      res.status(500).send({ error: err.message });
    });

    archive.pipe(res);
    archive.directory(pluginDir, "nexusbuilder");
    archive.finalize();
  });

  // Vite middleware for development
  if (process.env.NODE_ENV !== "production") {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: "spa",
    });
    app.use(vite.middlewares);
  } else {
    const distPath = path.join(process.cwd(), "dist");
    app.use(express.static(distPath));
    app.get("*", (req, res) => {
      res.sendFile(path.join(distPath, "index.html"));
    });
  }

  app.listen(PORT, "0.0.0.0", () => {
    console.log(`Server running on http://localhost:${PORT}`);
  });
}

startServer();
