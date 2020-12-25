# ReDecompiler
DevelStudio 3.0 EXE Decompiler Auto-DVS

### How to Install

0. Create new Devel Studio project
0. In project create a new event (On Create) and write the code: "ReDecompiler::Loader($self);"
0. Move "scripts" and "system" folders to the project folder

### Arguments

0. [-close] - Close program after decompile file
0. [-dump] - Getting file sections through dumper
0. [-dsc] - Double sections check

Example 1: "ReDecompiler.exe Test.exe -dump -close" \
Example 2: "ReDecompiler.exe Test.exe -close -dsc"

### How to app works

Application works with [PerfectConsole 1.1b](https://github.com/redeflesq/PerfectConsole)
