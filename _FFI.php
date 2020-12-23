<?php

class _FFI
{
    private $Libraries, $Built;

    function __construct()
    {
        $this->Libraries = array();
        $this->Built = false;
    }

    static function _Loader()
    {
        $_FFI = new _FFI();

        $_FFI->dll_add("msvcrt", "
		[lib='msvcrt.dll']
		  int system(char* _Command);
		");

        $_FFI->dll_add("kernel32", "
		[lib='Kernel32.dll']
		  BOOL WINAPI AllocConsole();
		  BOOL FreeConsole();
		  BOOL WINAPI AttachConsole(DWORD dwProcessId);
          HANDLE WINAPI GetStdHandle(DWORD nStdHandle);
		  BOOL SetConsoleTextAttribute(HANDLE hConsoleOutput, WORD wAttributes);
          BOOL WINAPI WriteConsoleA(HANDLE hConsoleOutput, char* lpBuffer, DWORD nNumberOfCharsToWrite, LPDWORD lpNumberOfCharsWritten, LPVOID lpReserved);
          int SetConsoleTitleA(char* lpConsoleTitle);
		  DWORD GetProcessId(HANDLE Process);
		");

        $_FFI->dll_add("user32", "
		[lib='User32.dll']
		  SHORT GetAsyncKeyState(int vKey);
		");

        $_FFI->dlls_build();

        return $_FFI;
    }

    public function dll_add($name, $header)
    {
        $this->Libraries[$name] = $header;
    }

    public function dlls_build()
    {
        if (!$this->Built) {
            foreach ($this->Libraries as $item => $value) {
                if (!($value instanceof FFI)) {
                    $this->Libraries[$item] = new FFI(_FFI_Helper::fix($value));
                }
            }
            $this->Built = true;
        }
    }

    public function dll_apply($name, $header)
    {
        $this->Libraries[$name] .= $header;
    }

    public function dlls_rebuild()
    {
        $this->Built = false;
        $this->dlls_build();
    }

    public function __call($name, $_args)
    {
        if (!$this->Built) {
            return false;
        }

        if (isset($_args[1])) {
            $args = $_args[1];
        } else {
            $args = array();
        }

        $_name = $_args[0];

        return eval(_FFI_Helper::eval_dll_args('$this->Libraries[$name]', '$_name', $args));
    }
}