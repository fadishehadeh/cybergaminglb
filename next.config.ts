import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "export",
  basePath: "/cybergaminglb/site",
  images: {
    unoptimized: true,
  },
};

export default nextConfig;
