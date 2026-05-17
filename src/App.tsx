import { useState } from 'react';
import { Download, CheckCircle, Package } from 'lucide-react';

export default function App() {
  const [downloading, setDownloading] = useState(false);

  const handleDownload = () => {
    setDownloading(true);
    // Give a slight visual delay for the user
    setTimeout(() => {
      window.location.href = '/api/download-plugin';
      setDownloading(false);
    }, 1500);
  };

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-6 font-sans">
      <div className="max-w-2xl w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        <div className="bg-gradient-to-r from-indigo-600 to-purple-600 p-10 text-center">
          <div className="bg-white/20 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 backdrop-blur-md">
            <Package className="w-10 h-10 text-white" />
          </div>
          <h1 className="text-4xl font-bold text-white mb-4 tracking-tight">NexusBuilder Plugin</h1>
          <p className="text-indigo-100 text-lg max-w-lg mx-auto">
            Your next-generation WordPress page builder is generated and ready. Download the plugin ZIP to install on your WordPress site.
          </p>
        </div>
        
        <div className="p-10">
          <h2 className="text-xl font-semibold text-gray-900 mb-6">What's included in this build:</h2>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
            {[
              "React 18 + Zustand visual editor",
              "Zero-runtime static HTML rendering",
              "50+ native elements & controls",
              "AI Assistant block generation",
              "GSAP scroll animations",
              "WooCommerce deep integration",
              "Global design tokens system",
              "Theme Builder (Headers/Footers)"
            ].map((feature, i) => (
              <div key={i} className="flex items-start gap-3">
                <CheckCircle className="w-5 h-5 text-indigo-500 shrink-0 mt-0.5" />
                <span className="text-gray-700">{feature}</span>
              </div>
            ))}
          </div>
          
          <div className="flex justify-center">
            <button
              onClick={handleDownload}
              disabled={downloading}
              className={`flex items-center gap-3 px-8 py-4 rounded-full font-semibold text-lg text-white transition-all transform hover:scale-105 active:scale-95 shadow-lg ${
                downloading 
                  ? 'bg-gray-400 cursor-not-allowed shadow-none' 
                  : 'bg-indigo-600 hover:bg-indigo-700 hover:shadow-indigo-500/25'
              }`}
            >
              {downloading ? (
                <>
                  <div className="w-6 h-6 border-3 border-white border-t-transparent rounded-full animate-spin" />
                  Preparing ZIP...
                </>
              ) : (
                <>
                  <Download className="w-6 h-6" />
                  Download Complete Plugin
                </>
              )}
            </button>
          </div>
          
          <p className="text-center text-sm text-gray-500 mt-6">
            Upload <code className="bg-gray-100 px-2 py-1 rounded">nexusbuilder.zip</code> via WordPress Admin ➔ Plugins ➔ Add New.
          </p>
        </div>
      </div>
    </div>
  );
}
