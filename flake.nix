{
  description = "Generate Nix expressions to build Composer packages";

  inputs.nixpkgs.url = "github:nixos/nixpkgs/nixos-unstable";
  inputs.flake-utils.url = "github:numtide/flake-utils";

  outputs = { self, nixpkgs, flake-utils }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = nixpkgs.legacyPackages.${system};
        p = import ./default.nix { inherit pkgs; };

        executable = exeName:
          flake-utils.lib.mkApp {
            drv = p;
            exePath = "/bin/${exeName}";
          };

        overlays = final: prev: { serenata = p; };
      in rec {
        packages.serenata = p;
        defaultPackage = packages.serenata;
        apps.console = executable "console";
        apps.createPhar = executable "create-phar";
        defaultApp = apps.console;
        inherit overlays;
      });
}
