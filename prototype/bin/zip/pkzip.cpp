#include "pkzip.h"

const unsigned int PKZip::SIGNATURE_CENTRAL_FILE          = 0x02014b50;  // "PK\x01\x02" Central file header
const unsigned int PKZip::SIGNATURE_LOCAL_FILE            = 0x04034b50;  // "PK\x03\x04" Local file header
const unsigned int PKZip::SIGNATURE_DIGITAL               = 0x05054b50;  // "PK\x05\x05" Digital
const unsigned int PKZip::SIGNATURE_END_RECORD            = 0x06054b50;  // "PK\x05\x06" End of central directory record
const unsigned int PKZip::SIGNATURE_ZIP64_END_RECORD      = 0x06064b50;  // "PK\x06\x06" Zip64 end of central directory record
const unsigned int PKZip::SIGNATURE_ZIP64_END_LOCATOR     = 0x07064b50;  // "PK\x06\x07" Zip64 end of central directory locator
const unsigned int PKZip::SIGNATURE_ARCHIVE_EXTRA_DATA    = 0x08064b50;  // "PK\x08\x06" Archive extra data


// Version made by (2 bytes)
const unsigned short PKZip::VERSION_MADE_MSDOS            = 0x0;           // MS-DOS and OS/2 (FAT / VFAT / FAT32 file systems)
const unsigned short PKZip::VERSION_MADE_AMIGA            = 0x1;           // Amiga
const unsigned short PKZip::VERSION_MADE_OPENVMS          = 0x2;           // OpenVMS
const unsigned short PKZip::VERSION_MADE_UNIX             = 0x3;           // UNIX
const unsigned short PKZip::VERSION_MADE_VMCMS            = 0x4;           // VM/CMS
const unsigned short PKZip::VERSION_MADE_ATARI            = 0x5;           // Atari ST
const unsigned short PKZip::VERSION_MADE_OS2              = 0x6;           // OS/2 H.P.F.S.
const unsigned short PKZip::VERSION_MADE_MACINTOSH        = 0x7;           // Macintosh
const unsigned short PKZip::VERSION_MADE_ZSYSTEM          = 0x8;           // Z-System
const unsigned short PKZip::VERSION_MADE_CPM              = 0x9;           // CP/M
const unsigned short PKZip::VERSION_MADE_WINDOWS_NTFS     = 0xA;          // Windows NTFS
const unsigned short PKZip::VERSION_MADE_MVS              = 0xB;          // MVS (OS/390 - Z/OS)
const unsigned short PKZip::VERSION_MADE_VSE              = 0xC;          // VSE
const unsigned short PKZip::VERSION_MADE_ACORN_RISC       = 0xD;          // Acorn Risc
const unsigned short PKZip::VERSION_MADE_VFAT             = 0xE;          // VFAT
const unsigned short PKZip::VERSION_MADE_ALTERNATE_MVS    = 0xF;          // alternate MVS
const unsigned short PKZip::VERSION_MADE_BEOS             = 0x10;          // BeOS
const unsigned short PKZip::VERSION_MADE_TANDEM           = 0x11;          // Tandem
const unsigned short PKZip::VERSION_MADE_OS400            = 0x12;          // OS/400
const unsigned short PKZip::VERSION_MADE_OSX              = 0x13;          // OS X (Darwin)

// Current minimum feature versions: (2 bytes)
const unsigned short PKZip::VERSION_1_0                   = 0xA;         // 1.0 - Default value
const unsigned short PKZip::VERSION_1_1                   = 0xB;         // 1.1 - File is a volume label
const unsigned short PKZip::VERSION_2_0                   = 0x14;        // 2.0 - File is a folder (directory), Deflate, PKWARE encryption
const unsigned short PKZip::VERSION_2_1                   = 0x15;        // 2.1 - File is compressed using Deflate64(tm)
const unsigned short PKZip::VERSION_2_5                   = 0x19;        // 2.5 - File is compressed using PKWARE DCL Implode
const unsigned short PKZip::VERSION_2_7                   = 0x1B;        // 2.7 - File is a patch data set
const unsigned short PKZip::VERSION_4_5                   = 0x2D;        // 4.5 - File uses ZIP64 format extensions
const unsigned short PKZip::VERSION_4_6                   = 0x2E;        // 4.6 - File is compressed using BZIP2 compression*
const unsigned short PKZip::VERSION_5_0                   = 0x32;        // 5.0 - File is encrypted using DES, 3DES, RC2, RC4 encryption
const unsigned short PKZip::VERSION_5_1                   = 0x33;        // 5.1 - File is encrypted using AES, RC2* encryption
const unsigned short PKZip::VERSION_5_2                   = 0x34;        // 5.2 - File is encrypted using RC2-64* encryption
const unsigned short PKZip::VERSION_6_1                   = 0x3D;        // 6.1 - File is encrypted using non-OAEP key wrapping
const unsigned short PKZip::VERSION_6_2                   = 0x3E;        // 6.2 - Central directory encryption
const unsigned short PKZip::VERSION_6_3                   = 0x3F;        // 6.3 - File is compressed using LZMA, PPMd+, Blowfish, Twofish

// General purpose bit flag: (2 bytes)
const unsigned short PKZip::FLAG_NONE                     = 0x0;
const unsigned short PKZip::FLAG_ENCRYPTED                = 1<<0;
const unsigned short PKZip::FLAG_COMPRESS_OPT1            = 1<<1;
const unsigned short PKZip::FLAG_COMPRESS_OPT2            = 1<<2;
const unsigned short PKZip::FLAG_DATA_DESCRIPTOR          = 1<<3;
const unsigned short PKZip::FLAG_ENHANCED_DEFLATION       = 1<<4;
const unsigned short PKZip::FLAG_COMPRESSED_PATCHED_DATA  = 1<<5;
const unsigned short PKZip::FLAG_STRONG_ENCRYPTION        = 1<<6;
const unsigned short PKZip::FLAG_UNUSED1                  = 1<<7;
const unsigned short PKZip::FLAG_UNUSED2                  = 1<<8;
const unsigned short PKZip::FLAG_UNUSED3                  = 1<<9;
const unsigned short PKZip::FLAG_UNUSED4                  = 1<<10;
const unsigned short PKZip::FLAG_LANG_ENCODING            = 1<<11;
const unsigned short PKZip::FLAG_RESERVED1                = 1<<12;
const unsigned short PKZip::FLAG_MASK_HEADER_VALUES       = 1<<13;
const unsigned short PKZip::FLAG_RESERVED2                = 1<<14;
const unsigned short PKZip::FLAG_RESERVED3                = 1<<15;

// Compression method: (2 bytes)
const unsigned short PKZip::CMETHOD_STORED                = 0x0;
const unsigned short PKZip::CMETHOD_SHRUNK                = 0x1;
const unsigned short PKZip::CMETHOD_REDUCED1              = 0x2;
const unsigned short PKZip::CMETHOD_REDUCED2              = 0x3;
const unsigned short PKZip::CMETHOD_REDUCED3              = 0x4;
const unsigned short PKZip::CMETHOD_REDUCED4              = 0x5;
const unsigned short PKZip::CMETHOD_IMPLODED              = 0x6;
const unsigned short PKZip::CMETHOD_RESERVED_TOKENIZING   = 0x7;
const unsigned short PKZip::CMETHOD_DEFLATED              = 0x8;
const unsigned short PKZip::CMETHOD_DEFLATE64             = 0x9;
const unsigned short PKZip::CMETHOD_PKWARE_IMPLODING      = 0xA;
const unsigned short PKZip::CMETHOD_RESERVED1             = 0xB;
const unsigned short PKZip::CMETHOD_BZIP2                 = 0xC;
const unsigned short PKZip::CMETHOD_RESERVED2             = 0xD;
const unsigned short PKZip::CMETHOD_LZMA                  = 0xE;
const unsigned short PKZip::CMETHOD_RESERVED3             = 0xF;
const unsigned short PKZip::CMETHOD_RESERVED4             = 0x10;
const unsigned short PKZip::CMETHOD_RESERVED5             = 0x11;
const unsigned short PKZip::CMETHOD_IBM_TERSE             = 0x12;
const unsigned short PKZip::CMETHOD_IBM_LZ77              = 0x13;
const unsigned short PKZip::CMETHOD_WAVPACK               = 0x61;
const unsigned short PKZip::CMETHOD_PPMD                  = 0x62;

// Initial DOS date (1980-01-01 00:00:00) in unix timestamp
const unsigned int PKZip::DOS_TIME_INIT                   = 315543600;

// R/W chunk size: 1<<15 = 32k, 1<<16 = 64k
const unsigned int PKZip::CHUNK                           = 1<<16;

PKZip::PKZip( std::string zdir ) {
	this->_zdir         = zdir;
	this->_zfile        = NULL;
	this->_sfile        = NULL;
	this->_zip_comment  = "";
	this->_level        = Z_BEST_COMPRESSION;
	this->_total_size   = 0;
	this->_proc_size    = 0;
	this->_proc_perc    = 0;
	this->_ctrl_dir_cnt = 0;
	this->_offset       = 0;
	this->_closed       = false;
	this->_ctrl_dir     = new std::stringstream( std::ios::in | std::ios::out | std::ios::binary );
}

PKZip::~PKZip() {
	if ( !this->_closed ) this->close();
}

PKZip* PKZip::setZipFile( std::string fname ) {
	this->_zfile = new std::ofstream( fname.c_str(), std::ios::out | std::ios::binary | std::ios::trunc );
	return this;
}

PKZip* PKZip::setStatusFile( std::string fname ) {
	this->_sfile = new std::ofstream( fname.c_str(), std::ios::out | std::ios::binary | std::ios::trunc );
	this->_sfile->write( (char*)&this->_proc_perc, 2 );
	return this;
}

PKZip* PKZip::setComment( std::string comment ) {
	this->_zip_comment = comment;
	return this;
}

PKZip* PKZip::setTotalUncompressSize( unsigned long int size ) {
	this->_total_size = size + 1;
	return this;
}

bool PKZip::addFile( std::string fname, std::string comment ) {
	boost::filesystem::path p( this->_zdir+'/'+fname );
	if ( !boost::filesystem::exists( p ) ) return false;

	std::stringstream xField( std::ios::in | std::ios::out | std::ios::binary );
	time_t            unixtime     = boost::filesystem::last_write_time( p ) ;
	unsigned int      unc_len      = boost::filesystem::file_size( p );
	unsigned int      rel_offset   = 32;
	unsigned int      c_len        = 0;
	unsigned short    fname_len    = fname.length();
	unsigned short    comment_len  = comment.length();
	unsigned long     crc          = 0;
	unsigned short    zero         = 0;
	unsigned short    xField_len   = 0;

	unixtime = ( unixtime == 0 )? std::time(0) : unixtime;

	time_t dostime = this->_unix2DosTime( unixtime );

	xField.clear();
	this->_xField_UnixOwner( xField )
		->_xField_Timestamp( xField, true, unixtime, unixtime, unixtime );
	xField_len = xField.str().length();

	this->_zfile->write( (char*)&PKZip::SIGNATURE_LOCAL_FILE, 4 );														// 4 bytes: local signature
	this->_zfile->write( (char*)&PKZip::VERSION_2_0,          2 );														// 2 bytes: ver needed to extract
	this->_zfile->write( (char*)&PKZip::FLAG_LANG_ENCODING,   2 );														// 2 bytes: gen purpose bit flag
	this->_zfile->write( (char*)&PKZip::CMETHOD_DEFLATED,     2 );														// 2 bytes: compression method
	this->_zfile->write( (char*)&dostime,                     4 );														// 4 bytes: last mod time and date

	unsigned long posCRC = this->_zfile->tellp();
	this->_zfile->seekp( posCRC+8 );

	this->_zfile->write( (char*)&unc_len,                     4 );														// 4 bytes: uncompressed filesize
	this->_zfile->write( (char*)&fname_len,                   2 );														// 2 bytes: length of filename
	this->_zfile->write( (char*)&xField_len,                  2 );														// 2 bytes: extra field length

	(*this->_zfile) << fname;																							// variable: file name
	(*this->_zfile) << xField.str();																					// variable: extra field

	// Process input file
	this->_gzcompress( p.string(), c_len, crc );																		// variable: compressed data
	unsigned long posDATA = this->_zfile->tellp();

	this->_zfile->seekp( posCRC );
	this->_zfile->write( (char*)&crc,                         4 );														// 4 bytes: crc32
	this->_zfile->write( (char*)&c_len,                       4 );														// 4 bytes: compressed filesize

	this->_zfile->seekp( posDATA );
	this->_zfile->write( (char*)&crc,                         4 );														// 4 bytes: crc32
	this->_zfile->write( (char*)&c_len,                       4 );														// 4 bytes: compressed filesize
	this->_zfile->write( (char*)&unc_len,                     4 );														// 4 bytes: uncompressed filesize

	xField.clear();
	this->_xField_UnixOwner( xField )
		->_xField_Timestamp( xField, false, unixtime, unixtime, unixtime );
	xField_len = xField.str().length();

	this->_ctrl_dir->write( (char*)&PKZip::SIGNATURE_CENTRAL_FILE, 4 );													// 4 bytes: central file header signature
	this->_ctrl_dir->write( (char*)&PKZip::VERSION_MADE_MSDOS,     2 );													// 2 bytes: version made by
	this->_ctrl_dir->write( (char*)&PKZip::VERSION_2_0,            2 );													// 2 bytes: ver needed to extract
	this->_ctrl_dir->write( (char*)&PKZip::FLAG_LANG_ENCODING,     2 );													// 2 bytes: gen purpose bit flag
	this->_ctrl_dir->write( (char*)&PKZip::CMETHOD_DEFLATED,       2 );													// 2 bytes: compression method
	this->_ctrl_dir->write( (char*)&dostime,                       4 );													// 4 bytes: last mod time and date
	this->_ctrl_dir->write( (char*)&crc,                           4 );													// 4 bytes: crc32
	this->_ctrl_dir->write( (char*)&c_len,                         4 );													// 4 bytes: compressed filesize
	this->_ctrl_dir->write( (char*)&unc_len,                       4 );													// 4 bytes: uncompressed filesize
	this->_ctrl_dir->write( (char*)&fname_len,                     2 );													// 2 bytes: length of filename
	this->_ctrl_dir->write( (char*)&xField_len,                    2 );													// 2 bytes: extra field length
	this->_ctrl_dir->write( (char*)&comment_len,                   2 );													// 2 bytes: file comment length
	this->_ctrl_dir->write( (char*)&zero,                          2 );													// 2 bytes: disk number start
	this->_ctrl_dir->write( (char*)&zero,                          2 );													// 2 bytes: internal file attributes
	this->_ctrl_dir->write( (char*)&rel_offset,                    4 );													// 4 bytes: external file attributes - 'archive' bit set
	this->_ctrl_dir->write( (char*)&this->_offset,                 4 );													// 4 bytes: relative offset of local header
	(*this->_ctrl_dir) << fname;																						// variable: file name (variable size)
	(*this->_ctrl_dir) << xField.str();																					// variable: extra field (variable size)
	(*this->_ctrl_dir) << comment;																						// variable: file comment (variable size)

	this->_ctrl_dir_cnt++;
	this->_offset = this->_zfile->tellp();
	return true;
}

bool PKZip::close() {
	if ( this->_closed ) return false;

	this->_closed = true;
	if ( this->_ctrl_dir_cnt > 0 ) this->_writeCentralSection();
	this->_zfile->close();

	this->_setProgress( this->_total_size - this->_proc_size );
	if( this->_sfile ) this->_sfile->close();

	return true;
}

bool PKZip::_setProgress( unsigned long int add_size ) {
	this->_proc_size += add_size;
	unsigned short _perc = (this->_proc_size > 0)? (this->_proc_size*100)/this->_total_size : 0;
	if ( this->_sfile && _perc != this->_proc_perc ) {
		this->_proc_perc =_perc;
		this->_sfile->seekp( 0, std::ios::beg );
		this->_sfile->write( (char*)&_perc, 2 );
		this->_sfile->flush();
	}
	return true;
}

bool PKZip::_writeCentralSection() {
	unsigned short zero         = 0;
	unsigned short comment_len  = this->_zip_comment.length();
	unsigned int   ctrl_dir_len = this->_ctrl_dir->str().size();

	(*this->_zfile) << this->_ctrl_dir->str();																			// variable: full central dir
	this->_zfile->write( (char*)&PKZip::SIGNATURE_END_RECORD, 4 );														// 4 bytes: end of central dir signature
	this->_zfile->write( (char*)&zero,                        2 );														// 2 bytes: number of this disk
	this->_zfile->write( (char*)&zero,                        2 );														// 2 bytes: number of the disk with the start of the central directory
	this->_zfile->write( (char*)&this->_ctrl_dir_cnt,         2 );														// 2 bytes: total number of entries in the central directory on this disk
	this->_zfile->write( (char*)&this->_ctrl_dir_cnt,         2 );														// 2 bytes: total number of entries in the central directory
	this->_zfile->write( (char*)&ctrl_dir_len,                4 );														// 4 bytes: size of the central directory
	this->_zfile->write( (char*)&this->_offset,               4 );														// 4 bytes: offset of start of central directory with respect to the starting disk number
	this->_zfile->write( (char*)&comment_len,                 2 );														// 2 bytes: .ZIP file comment length
	(*this->_zfile) << this->_zip_comment;																				// variable: .ZIP file comment

	return true;
}

/**
 * -Extended Timestamp Extra Field:
 * ==============================
 *
 * The following is the layout of the extended-timestamp extra block. (Last Revision 19970118)
 *
 * Local-header version:
 *
 * Value         Size        Description
 * -----         ----        -----------
 * 0x5455        Short       tag for this extra block type ("UT")
 * TSize         Short       total data size for this block
 * Flags         Byte        info bits
 * (ModTime)     Long        time of last modification (UTC/GMT)
 * (AcTime)      Long        time of last access (UTC/GMT)
 * (CrTime)      Long        time of original creation (UTC/GMT)
 *
 * Central-header version:
 *
 * Value         Size        Description
 * -----         ----        -----------
 * 0x5455        Short       tag for this extra block type ("UT")
 * TSize         Short       total data size for this block
 * Flags         Byte        info bits (refers to local header!)
 * (ModTime)     Long        time of last modification (UTC/GMT)
 *
 * The central-header extra field contains the modification time only, or no timestamp at all. TSize is used to
 * flag its presence or absence. But note:
 *     If "Flags" indicates that Modtime is present in the local header field, it MUST be present in the central
 *     header field, too! This correspondence is required because the modification time value may be used to support
 *     trans-timezone freshening and updating operations with zip archives.
 *
 * The time values are in standard Unix signed-long format, indicating the number of seconds since 1 January 1970
 * 00:00:00. The times are relative to Coordinated Universal Time (UTC), also sometimes referred to as Greenwich
 * Mean Time (GMT). To convert to local time, the software must know the local timezone offset from UTC/GMT.
 *
 * The lower three bits of Flags in both headers indicate which timestamps are present in the LOCAL extra field:
 *     bit 0           if set, modification time is present
 *     bit 1           if set, access time is present
 *     bit 2           if set, creation time is present
 *     bits 3-7        reserved for additional timestamps; not set
 * Those times that are present will appear in the order indicated, but any combination of times may be omitted.
 * (Creation time may be present without access time, for example.) TSize should equal (1 + 4*(number of set bits in
 * Flags)), as the block is currently defined. Other timestamps may be added in the future.
 */
PKZip* PKZip::_xField_Timestamp( std::stringstream& buf, bool isLocal, time_t modTime, time_t acTime, time_t crTime ) {
	char flags = (((bool)modTime)<<0)|(((bool)acTime)<<1)|(((bool)crTime)<<2);
	char wrtTime = isLocal? flags : flags&0x1;
	unsigned short hdr = 0x5455;
	unsigned short len = 1 + ((wrtTime&1)<<2) + ((wrtTime&2)<<1) + ((wrtTime&4)<<0);

	buf.write((char*)&hdr,                      2 );																	// tag for this extra block type ("UT")
	buf.write((char*)&len,                      2 );																	// total data size for this block
	buf.write((char*)&flags,                    1 );																	// info bits
	if ( wrtTime&1 ) buf.write((char*)&modTime, 4 );																	// time of last modification (UTC/GMT)
	if ( wrtTime&2 ) buf.write((char*)&acTime,  4 );																	// time of last access (UTC/GMT)
	if ( wrtTime&4 ) buf.write((char*)&crTime,  4 );																	// time of original creation (UTC/GMT)
	return this;
}
/**
 * -Info-ZIP New Unix Extra Field:
 *  ====================================
 *
 *  Currently stores Unix UIDs/GIDs up to 32 bits.
 *  (Last Revision 20080509)
 *
 *  Value         Size        Description
 *  -----         ----        -----------
 *  0x7875        Short       tag for this extra block type ("ux")
 *  TSize         Short       total data size for this block
 *  Version       1 byte      version of this extra field, currently 1
 *  UIDSize       1 byte      Size of UID field
 *  UID           Variable    UID for this entry
 *  GIDSize       1 byte      Size of GID field
 *  GID           Variable    GID for this entry
 */
PKZip* PKZip::_xField_UnixOwner( std::stringstream& buf, unsigned short size, unsigned int uid, unsigned int gid ) {
	size = ( size == 1 )? 1 : ( ( size == 2 )? 2 : 4 );
	unsigned short hdr = 0x7875;
	unsigned short len = 3+size*2;
	unsigned short ver = 1;
	buf.write((char*)&hdr,                 2 );																			// tag for this extra block type ("ux")
	buf.write((char*)&len,                 2 );																			// total data size for this block
	buf.write((char*)&ver,                 1 );																			// version of this extra field, currently 1
	buf.write((char*)&size,                1 );																			// Size of UID field
	buf.write((char*)&uid,              size );																			// UID for this entry
	buf.write((char*)&size,                1 );																			// Size of GID field
	buf.write((char*)&gid,              size );																			// GID for this entry
	return this;
}

/**
 * Compress from file source to file dest until EOF on source.
 * def() returns Z_OK on success, Z_MEM_ERROR if memory could not be
 * allocated for processing, Z_STREAM_ERROR if an invalid compression
 * level is supplied, Z_VERSION_ERROR if the version of zlib.h and the
 * version of the library linked do not match, or Z_ERRNO if there is
 * an error reading or writing the files.
 */
bool PKZip::_gzcompress( std::string fname, unsigned int& size, unsigned long& crc ) {
	std::ifstream fin ( fname.c_str(), std::ios::binary );
	int ret, flush;
	z_stream strm;
	unsigned char in[PKZip::CHUNK];
	unsigned char out[PKZip::CHUNK];
	unsigned char* p;
	crc = crc32( 0L, Z_NULL, 0 );
	size = 0;
	int skip = 0;

	try {
		// allocate deflate state
		strm.zalloc = Z_NULL;
		strm.zfree = Z_NULL;
		strm.opaque = Z_NULL;

		ret = deflateInit( &strm, this->_level );
		if ( ret != Z_OK ) throw std::runtime_error( "deflateInit error" );

		// compress until end of file
		do {
			fin.read( (char*)in, PKZip::CHUNK );
			if ( fin.bad() ) {
				deflateEnd( &strm );
				return Z_ERRNO;
			}
			strm.avail_in = fin.gcount();
			crc = crc32( crc, in, fin.gcount() );
			flush = fin.eof()? Z_FINISH : Z_NO_FLUSH;
			strm.next_in = in;
			do {
				strm.avail_out = PKZip::CHUNK;
				strm.next_out = out;
				ret = deflate( &strm, flush );
				if ( ret == Z_STREAM_ERROR ) throw std::runtime_error( "deflate error" );

				unsigned int wrt_len = PKZip::CHUNK - strm.avail_out;

				// fix crc bug
				p = out; while ( skip < 2 && wrt_len > 0 ){ skip++; p++; wrt_len--; }

				this->_zfile->write( (char*)p, wrt_len );
				if ( this->_zfile->bad() ) throw std::runtime_error( "write error" );
				size += wrt_len;

			} while ( strm.avail_out == 0 );
			if ( strm.avail_in != 0 ) throw std::runtime_error( "avail_in error" );

			this->_setProgress( fin.gcount() );

		} while ( flush != Z_FINISH );
		if ( ret != Z_STREAM_END ) throw std::runtime_error( "Z_STREAM_END error" );

		// fix crc bug
		if ( size >= 4 ) {
			unsigned long pos = this->_zfile->tellp();
			this->_zfile->seekp( pos - 4 );
			size -= 4;
		}

	} catch( const std::exception& e ) {
		deflateEnd( &strm );
		fin.close();
		return false;
	}
	deflateEnd( &strm );
	fin.close();
	return true;
}

/**
 * Convert unix timestamp to standard MS-DOS format
 *
 * Standard MS-DOS format:
 * Bits 00-04: seconds divided by 2
 * Bits 05-10: minute
 * Bits 11-15: hour
 * Bits 16-20: day
 * Bits 21-24: month
 * Bits 25-31: years from 1980
 *
 */
time_t PKZip::_unix2DosTime( time_t unixtime ) {
	unixtime = std::max( unixtime, (time_t)PKZip::DOS_TIME_INIT );

	std::tm* udate = std::localtime( &unixtime );
	//std::tm* udate = std::gmtime( &unixtime );

	return
		( ( udate->tm_year-80 ) << 25 ) |
		( ( udate->tm_mon+1 ) << 21 ) |
		( ( udate->tm_mday ) << 16 ) |
		( ( udate->tm_hour ) << 11 ) |
		( ( udate->tm_min ) << 5 ) |
		( std::min( udate->tm_sec, 0x3B ) ) >> 1;
}
